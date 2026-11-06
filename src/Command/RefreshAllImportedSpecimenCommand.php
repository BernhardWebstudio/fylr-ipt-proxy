<?php

namespace App\Command;

use App\Repository\OccurrenceImportRepository;
use App\Service\EasydbApiService;
use App\Service\SpecimenImportService;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\ResetInterface;

#[AsCommand(
    name: 'app:refresh-all-imported-specimen',
    description: 'Re-load all imported specimens from EasyDB and map them again, updating them in the local database',
)]
class RefreshAllImportedSpecimenCommand extends Command
{
    public function __construct(
        private readonly OccurrenceImportRepository $occurrenceImportRepository,
        private readonly SpecimenImportService $specimenImportService,
        private readonly EasydbApiService $easydbApiService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('login', 'l', InputOption::VALUE_OPTIONAL, 'EasyDB login username')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'EasyDB login password')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without making any changes to the database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-mapping even if remote data has not changed (useful after fixing mapping code)')
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Start at this offset (for resuming interrupted runs)', 0)
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for flushing and clearing memory', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $login = $input->getOption('login') ?? $_ENV['EASYDB_LOGIN'] ?? getenv('EASYDB_LOGIN');
        $password = $input->getOption('password') ?? $_ENV['EASYDB_PASSWORD'] ?? getenv('EASYDB_PASSWORD');
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');
        $offset = max(0, (int) $input->getOption('offset'));
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        // Disable DBAL SQL logging to prevent query logs accumulating in memory
        $connection = $this->entityManager->getConnection();
        $config = $connection->getConfiguration();
        if (method_exists($config, 'setSQLLogger')) {
            $config->setSQLLogger(null);
        }
        if (method_exists($config, 'getMiddlewares')) {
            $filteredMiddlewares = array_filter(
                $config->getMiddlewares(),
                fn($m) => !($m instanceof LoggingMiddleware)
            );
            $config->setMiddlewares($filteredMiddlewares);
        }

        // Initialize EasyDB session with credentials if provided
        if ($login && $password) {
            $io->info('Authenticating with EasyDB...');
            if (!$this->easydbApiService->initializeFromLoginPassword($login, $password)) {
                $io->error('Failed to initialize EasyDB session with provided credentials.');
                return Command::FAILURE;
            }
            $io->success('Successfully authenticated with EasyDB.');
        } elseif (!$this->easydbApiService->hasValidSession()) {
            $io->error('No valid EasyDB session. Please provide --login and --password options.');
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be persisted to the database');
        }

        if ($force) {
            $io->note('FORCE MODE - Re-mapping all specimens even if remote data has not changed');
        }

        // Get total count first (without loading all entities)
        $io->section('Fetching all imported specimens from database...');
        $totalCount = $this->occurrenceImportRepository->getTotalCount();

        if ($totalCount === 0) {
            $io->warning('No imported specimens found in the database.');
            return Command::SUCCESS;
        }

        $remainingCount = $totalCount - $offset;
        if ($remainingCount <= 0) {
            $io->warning(sprintf('Offset %d is greater than or equal to total count %d.', $offset, $totalCount));
            return Command::SUCCESS;
        }

        if ($offset > 0) {
            $io->info(sprintf('Resuming from offset %d (%d of %d specimens remaining to refresh)', $offset, $remainingCount, $totalCount));
        } else {
            $io->info(sprintf('Found %d specimens to refresh', $totalCount));
        }

        // Process each specimen using iteration to avoid memory issues
        $io->section('Refreshing specimens from EasyDB...');
        $progressBar = $io->createProgressBar($remainingCount);
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $errors = [];
        $processedCount = 0;

        // Keyset pagination: fetch only a small chunk of rows at a time. A single
        // toIterable() over a buffered MySQL result keeps all rows in PHP memory.
        $lastId = null;
        $skipped = $offset;
        $aborted = false;

        while (!$aborted) {
            $qb = $this->occurrenceImportRepository->createQueryBuilder('oi')
                ->select('oi.id AS id, oi.globalObjectID AS gid')
                ->orderBy('oi.id', 'ASC')
                ->setMaxResults($batchSize);
            if ($lastId !== null) {
                $qb->where('oi.id > :lastId')->setParameter('lastId', $lastId);
            } elseif ($skipped > 0) {
                $qb->setFirstResult($skipped);
            }
            $rows = $qb->getQuery()->getArrayResult();
            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = $row['id'];
                $globalObjectId = $row['gid'];


                try {
                    // Re-import the specimen using the service
                    $wasUpdated = $this->specimenImportService->importByGlobalObjectId(
                        $globalObjectId,
                        null, // no specific user
                        true, // don't flush yet - we'll batch flush
                        $force // force re-mapping even if remote hasn't changed
                    );

                    if ($wasUpdated) {
                        $successCount++;
                    } else {
                        $skippedCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = sprintf(
                        'Failed to refresh %s: %s',
                        $globalObjectId,
                        $e->getMessage()
                    );
                    $errors[] = $errorMessage;

                    $this->logger->error('Error refreshing specimen', [
                        'globalObjectId' => $globalObjectId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    if (!$this->entityManager->isOpen()) {
                        $io->error('The EntityManager is closed. Aborting execution to prevent cascading errors.');
                        $aborted = true;
                        break;
                    }
                }

                $processedCount++;
                $progressBar->advance();
            }

            if (!$dryRun && $this->entityManager->isOpen()) {
                $this->entityManager->flush();
            }
            $this->entityManager->clear();
            if ($this->logger instanceof ResetInterface) {
                $this->logger->reset();
            }
            gc_collect_cycles();
        }

        // Final flush for remaining items
        if (!$dryRun && $this->entityManager->isOpen()) {
            $this->entityManager->flush();
        }

        // Final clear
        $this->entityManager->clear();

        $progressBar->finish();
        $io->newLine(2);

        // Display summary
        $io->section('Summary');
        $io->table(
            ['Result', 'Count'],
            [
                ['Total Specimens', $totalCount],
                ['Successfully Refreshed', $successCount],
                ['Errors', $errorCount],
                ['Skipped', $skippedCount],
            ]
        );

        // Display errors if any
        if ($errorCount > 0) {
            $io->section('Errors');
            $io->listing(array_slice($errors, 0, 10)); // Show first 10 errors
            if (count($errors) > 10) {
                $io->note(sprintf('... and %d more errors. Check logs for details.', count($errors) - 10));
            }
        }

        if ($dryRun) {
            $io->note('DRY RUN completed - no changes were persisted');
        }

        if ($errorCount > 0) {
            $io->warning(sprintf(
                'Refresh completed with %d errors. Successfully refreshed %d of %d specimens.',
                $errorCount,
                $successCount,
                $totalCount
            ));
            return Command::FAILURE;
        }

        $io->success(sprintf(
            'All specimens refreshed successfully! Updated %d specimens.',
            $successCount
        ));

        return Command::SUCCESS;
    }
}
