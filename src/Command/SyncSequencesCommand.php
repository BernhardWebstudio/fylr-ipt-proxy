<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-sequences',
    description: 'Synchronize PostgreSQL identity/serial sequences with the maximum ID in each table',
)]
class SyncSequencesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        $tables = [
            'dwc_chronometric_age',
            'dwc_event',
            'dwc_geological_context',
            'dwc_identification',
            'dwc_location',
            'dwc_material_entity',
            'dwc_material_sample',
            'dwc_measurement_or_fact',
            'dwc_occurrence',
            'dwc_organism',
            'dwc_resource_relationship',
            'dwc_taxon',
            'easydb_object_type',
            'job_status',
            'occurrence_import',
            'user',
            'messenger_messages',
        ];

        $io->section('Synchronizing PostgreSQL Sequences...');

        $rows = [];
        $hasErrors = false;

        foreach ($tables as $table) {
            $quotedTable = $table === 'user' ? '"user"' : $table;

            try {
                // 1. Get the sequence name
                $seqNameResult = $connection->fetchOne(sprintf(
                    "SELECT pg_get_serial_sequence('%s', 'id')",
                    $table === 'user' ? '"user"' : $table
                ));

                if (!$seqNameResult) {
                    $rows[] = [$table, 'N/A', 'N/A', 'No sequence associated with id column'];
                    continue;
                }

                // 2. Get the maximum ID in the table
                $maxId = (int) $connection->fetchOne(sprintf("SELECT COALESCE(MAX(id), 0) FROM %s", $quotedTable));

                // 3. Get the current sequence value (optional, for display)
                $currentVal = null;
                try {
                    $currentVal = (int) $connection->fetchOne(sprintf("SELECT last_value FROM %s", $seqNameResult));
                } catch (\Exception $e) {
                    // Sequence might not have been called yet
                    $currentVal = 'N/A';
                }

                // 4. Update the sequence using setval
                // If maxId is 0 (empty table), we restart/set it to 1 and mark is_called as false so the first nextval returns 1
                if ($maxId === 0) {
                    $connection->executeStatement(sprintf(
                        "SELECT setval('%s', 1, false)",
                        $seqNameResult
                    ));
                    $newVal = 1;
                    $status = 'Reset to 1 (empty table)';
                } else {
                    $connection->executeStatement(sprintf(
                        "SELECT setval('%s', %d, true)",
                        $seqNameResult,
                        $maxId
                    ));
                    $newVal = $maxId;
                    $status = 'Synchronized';
                }

                $rows[] = [$table, $currentVal, $newVal, $status];
            } catch (\Exception $e) {
                $hasErrors = true;
                $rows[] = [$table, 'ERROR', 'ERROR', $e->getMessage()];
            }
        }

        $io->table(
            ['Table', 'Old Sequence Val', 'New Sequence Val', 'Status'],
            $rows
        );

        if ($hasErrors) {
            $io->error('Some sequences could not be synchronized.');
            return Command::FAILURE;
        }

        $io->success('All sequences synchronized successfully.');
        return Command::SUCCESS;
    }
}
