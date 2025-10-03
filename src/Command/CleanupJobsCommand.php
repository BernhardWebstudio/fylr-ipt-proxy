<?php

namespace App\Command;

use App\Service\JobStatusService;
// use App\MessageHandler\ExportDataMessageHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-jobs',
    description: 'Clean up old completed jobs and export files',
)]
class CleanupJobsCommand extends Command
{
    public function __construct(
        private JobStatusService $jobStatusService,
        // private ExportDataMessageHandler $exportHandler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days old jobs to keep', 30)
            ->setHelp('This command allows you to clean up old completed jobs and export files...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getArgument('days');

        if ($days < 1) {
            $io->error('Days must be a positive integer.');
            return Command::FAILURE;
        }

        $io->note("Cleaning up jobs and files older than {$days} days...");

        // Clean up old jobs
        $deletedJobs = $this->jobStatusService->cleanupOldJobs($days);
        $io->success("Deleted {$deletedJobs} old job records.");

        // Clean up old export files
        // $deletedFiles = $this->exportHandler->cleanupOldExports($days);
        // $io->success("Deleted {$deletedFiles} old export files.");

        $io->success('Cleanup completed successfully.');

        return Command::SUCCESS;
    }
}
