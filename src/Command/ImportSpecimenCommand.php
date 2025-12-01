<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

#[AsCommand(
    name: 'app:import-specimen',
    description: 'Import specimens from EasyDB into the local database',
)]
class ImportSpecimenCommand extends Command
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly HttpKernelInterface $httpKernel,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('globalObjectId', InputArgument::IS_ARRAY, 'The global object IDs to import (separate multiple IDs with a space)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $globalObjectIds = $input->getArgument('globalObjectId');

        if (empty($globalObjectIds)) {
            $io->warning('No globalObjectId arguments provided. Nothing to import.');
            return Command::INVALID;
        }

        $successCount = 0;

        foreach ($globalObjectIds as $goi) {
            $io->section(sprintf('Importing globalObjectId: %s', $goi));
            try {
                // Generate the path for the internal route call.
                $path = $this->router->generate('app_import_management_one_goi', [
                    'globalObjectId' => $goi,
                ]);

                // Create a sub-request (POST as defined in route) without referer/session context.
                $request = Request::create($path, 'POST');

                // Handle as sub-request to avoid affecting main kernel state.
                $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);

                $statusCode = $response->getStatusCode();
                if ($statusCode >= 300) {
                    $bodyExcerpt = substr($response->getContent(), 0, 500);
                    $io->error(sprintf('Failed importing %s. Status %d. Response: %s', $goi, $statusCode, $bodyExcerpt));
                    $io->writeln(sprintf('Successful imports before failure: %d', $successCount));
                    return Command::FAILURE;
                }

                $successCount++;
                $io->success(sprintf('Successfully imported %s (status %d).', $goi, $statusCode));
            } catch (\Throwable $e) {
                $io->error(sprintf('Exception while importing %s: %s', $goi, $e->getMessage()));
                $io->writeln(sprintf('Successful imports before failure: %d', $successCount));
                return Command::FAILURE;
            }
        }

        $io->success(sprintf('All imports completed. Total successes: %d', $successCount));
        return Command::SUCCESS;
    }
}
