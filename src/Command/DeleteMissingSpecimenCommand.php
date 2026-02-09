<?php

namespace App\Command;

use App\Repository\OccurrenceImportRepository;
use App\Service\EasydbApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-missing-specimen',
    description: 'Delete local specimens that are no longer found in EasyDB/Fylr',
)]
class DeleteMissingSpecimenCommand extends Command
{
    public function __construct(
        private readonly OccurrenceImportRepository $occurrenceImportRepository,
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
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Flush/clear batch size', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $login = $input->getOption('login') ?? getenv('EASYDB_LOGIN');
        $password = $input->getOption('password') ?? getenv('EASYDB_PASSWORD');
        $dryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(1, (int) $input->getOption('batch-size'));

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

        $io->section('Fetching all imported specimens from database...');
        $totalCount = $this->occurrenceImportRepository->getTotalCount();

        if ($totalCount === 0) {
            $io->warning('No imported specimens found in the database.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d specimens to verify', $totalCount));

        $io->section('Checking specimens against EasyDB/Fylr...');
        $progressBar = $io->createProgressBar($totalCount);
        $progressBar->start();

        $deletedCount = 0;
        $keptCount = 0;
        $errorCount = 0;
        $errors = [];
        $processedCount = 0;

        $queryBuilder = $this->occurrenceImportRepository->createQueryBuilder('oi');
        $query = $queryBuilder->getQuery();

        foreach ($query->toIterable() as $import) {
            $globalObjectId = $import->getGlobalObjectID();

            try {
                $entityData = $this->easydbApiService->loadEntityByGlobalObjectID($globalObjectId);
                if ($entityData === null) {
                    $deletedCount++;
                    if (!$dryRun) {
                        $this->entityManager->remove($import);
                    }
                } else {
                    $keptCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = sprintf('Failed to verify %s: %s', $globalObjectId, $e->getMessage());
                $this->logger->error('Error checking specimen against EasyDB', [
                    'globalObjectId' => $globalObjectId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $processedCount++;
            $progressBar->advance();

            if ($processedCount % $batchSize === 0) {
                if (!$dryRun) {
                    $this->entityManager->flush();
                }
                $this->entityManager->clear();
                gc_collect_cycles();
            }
        }

        if (!$dryRun && ($processedCount % $batchSize !== 0)) {
            $this->entityManager->flush();
        }

        $this->entityManager->clear();

        $progressBar->finish();
        $io->newLine(2);

        $io->section('Summary');
        $io->table(
            ['Result', 'Count'],
            [
                ['Total Specimens', $totalCount],
                ['Kept (found in EasyDB)', $keptCount],
                ['Deleted (missing in EasyDB)', $deletedCount],
                ['Errors', $errorCount],
            ]
        );

        if ($errorCount > 0) {
            $io->section('Errors');
            $io->listing(array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $io->note(sprintf('... and %d more errors. Check logs for details.', count($errors) - 10));
            }
        }

        if ($dryRun) {
            $io->note('DRY RUN completed - no changes were persisted');
        }

        if ($errorCount > 0) {
            $io->warning(sprintf('Completed with %d errors. Deleted %d of %d specimens.', $errorCount, $deletedCount, $totalCount));
            return Command::FAILURE;
        }

        $io->success(sprintf('Completed. Deleted %d of %d specimens.', $deletedCount, $totalCount));

        return Command::SUCCESS;
    }
}
