<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Synchronize identity sequences for all tables that were converted to IDENTITY columns in Version20260623060122.
 */
final class Version20260624170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronize identity sequences for all tables converted to IDENTITY columns';
    }

    public function up(Schema $schema): void
    {
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

        foreach ($tables as $table) {
            $quotedTable = $table === 'user' ? '"user"' : $table;
            $this->addSql(sprintf(
                "SELECT setval(pg_get_serial_sequence('%s', 'id'), COALESCE(MAX(id), 1)) FROM %s",
                $quotedTable,
                $quotedTable
            ));
        }
    }

    public function down(Schema $schema): void
    {
        // No-op as this is a one-way synchronization of sequences
    }
}
