<?php

namespace App\Command;

use App\Service\EasydbApiService;
use App\Service\SpecimenImportService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'app:import-all-by-tag',
    description: 'Import all items from EasyDB by tag ID into the local database',
)]
class ImportAllByTagCommand extends Command
{
    public function __construct(
        private readonly SpecimenImportService $specimenImportService,
        private readonly EasydbApiService $easydbApiService,
        #[AutowireIterator("app.easydb_dwc_mapping")] private readonly iterable $mappings,
        private readonly LockFactory $lockFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('query', InputArgument::REQUIRED, 'Tag ID to find specimens')
            ->addOption('login', 'l', InputOption::VALUE_OPTIONAL, 'EasyDB login username')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'EasyDB login password')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-mapping even if remote data has not changed')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Start at this offset (for resuming after errors)', 0)
            ->addOption('retry-on-close', null, InputOption::VALUE_OPTIONAL, 'Number of times to retry the command if EntityManager closes', 3)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $tagId = $input->getArgument('query');
        $login = $input->getOption('login') ?? getenv('EASYDB_LOGIN');
        $password = $input->getOption('password') ?? getenv('EASYDB_PASSWORD');
        $force = (bool) $input->getOption('force');
        $initialOffset = (int) $input->getOption('offset');
        $retryOnClose = (int) $input->getOption('retry-on-close');
        $retryCount = 0;

        $lock = $this->lockFactory->createLock('import-all-by-tag-' . $tagId);
        if (!$lock->acquire()) {
            $io->error('Another instance of this command is already running for tag ' . $tagId);
            return Command::FAILURE;
        }

        try {
        if ($login && $password) {
            if (!$this->easydbApiService->initializeFromLoginPassword($login, $password)) {
                $io->error('Failed to initialize EasyDB session with provided credentials.');
                return Command::FAILURE;
            }
            $io->info('Successfully authenticated with EasyDB.');
        } elseif (!$this->easydbApiService->hasValidSession()) {
            $io->error('No valid EasyDB session. Please provide --login and --password options.');
            return Command::FAILURE;
        }

        $numPerPage = 50;
        $offset = $initialOffset;

        // First request to get total count (we get full response by passing reduceToObjects=false)
        try {
            $searchResults = $this->easydbApiService->searchByTag((int) $tagId, null, $offset, $numPerPage, false);
        } catch (\Throwable $e) {
            $io->error(sprintf('Error while fetching search results: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $totalNum = (int) ($searchResults['count'] ?? count($searchResults['objects'] ?? []));

        if ($totalNum === 0) {
            $io->warning(sprintf('No objects found for tag %s', $tagId));
            return Command::SUCCESS;
        }

        if ($initialOffset > 0) {
            $io->section(sprintf('Resuming from offset %d. Found %d object(s) total for tag %s', $initialOffset, $totalNum, $tagId));
        } else {
            $io->section(sprintf('Found %d object(s) for tag %s', $totalNum, $tagId));
        }

        $progressBar = $io->createProgressBar($totalNum - $initialOffset);
        $progressBar->start();

        $processed = 0;
        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $entityManagerClosedCount = 0;
        $errors = [];
        $lastSuccessfulOffset = $initialOffset;

        // Paging loop
        while ($offset < $totalNum) {
            // Ensure we fetch the latest page of results
            try {
                $results = $this->easydbApiService->searchByTag((int) $tagId, null, $offset, $numPerPage, false);
            } catch (\Throwable $e) {
                $io->error(sprintf('Failed to fetch page at offset %d: %s', $offset, $e->getMessage()));
                $errorCount++;
                $errors[] = sprintf('Fetch at offset %d failed: %s', $offset, $e->getMessage());
                break; // break out - can't continue fetching
            }

            $objects = $results['objects'] ?? [];
            if (empty($objects)) {
                break;
            }

            foreach ($objects as $object) {

                try {
                    $wasUpdated = $this->specimenImportService->importByEntityData($object, null, true, $force);
                    if ($wasUpdated) {
                        $successCount++;
                    } else {
                        $skippedCount++;
                    }
                    $lastSuccessfulOffset = $offset + $processed;
                } catch (EntityManagerClosed $e) {
                    // EntityManager was closed due to an error
                    $entityManagerClosedCount++;
                    $errorCount++;
                    if (count($errors) < 1000) {
                        $errors[] = sprintf('EntityManager closed during import: %s', $e->getMessage());
                    }
                    $io->error(sprintf('EntityManager closed at offset %d: %s', $offset + $processed, $e->getMessage()));

                    // Close the EntityManager to reset it
                    if ($this->entityManager->isOpen()) {
                        $this->entityManager->close();
                    }

                    // If we've hit too many EntityManager closures, exit and let the command retry from this point
                    if ($entityManagerClosedCount >= $retryOnClose) {
                        $io->warning(sprintf('EntityManager closed %d times. Exiting to retry with fresh process.', $entityManagerClosedCount));
                        $progressBar->finish();
                        $io->newLine(2);

                        // Output retry instruction
                        $io->section('Resuming Import');
                        $io->text(sprintf('To resume from where we left off, run:'));
                        $io->text(sprintf('<info>bin/console app:import-all-by-tag %s --offset=%d --retry-on-close=%d</info>', $tagId, $lastSuccessfulOffset, $retryOnClose));

                        // Still return failure because we didn't complete
                        return Command::FAILURE;
                    }
                } catch (\Throwable $e) {
                    $errorCount++;
                    // Limit error array to prevent memory issues
                    if (count($errors) < 1000) {
                        $errors[] = sprintf('Import failed for object: %s', $e->getMessage());
                    }
                    $io->error(sprintf('Error importing object: %s', $e->getMessage()));
                    // Continue with next item
                }

                $processed++;
                $progressBar->advance();

                // Clear EntityManager periodically to prevent memory exhaustion
                if ($processed % 50 === 0) {
                    $this->safeClearEntityManager();
                    gc_collect_cycles();
                }
            }

            $offset += count($objects);
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->section('Summary');
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total objects found', $totalNum],
                ['Processed', $processed],
                ['Successfully imported', $successCount],
                ['Skipped', $skippedCount],
                ['Errors', $errorCount],
                ['EntityManager closures', $entityManagerClosedCount],
            ]
        );

        if ($errorCount > 0) {
            $io->section('Errors (first 10)');
            $io->listing(array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $io->note(sprintf('... and %d more errors (check logs for details).', count($errors) - 10));
            }
        }

        if ($errorCount > 0) {
            $io->warning(sprintf('Import completed with %d errors', $errorCount));
            return Command::FAILURE;
        }

        $io->success(sprintf('Import completed. %d objects imported, %d skipped.', $successCount, $skippedCount));

        return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    /**
     * Safely clear the EntityManager, closing it if it's already closed
     */
    private function safeClearEntityManager(): void
    {
        try {
            if ($this->entityManager->isOpen()) {
                $this->entityManager->clear();
            }
        } catch (EntityManagerClosed $e) {
            // If it's already closed, just close it to reset the state
            $this->entityManager->close();
        }
    }
}
