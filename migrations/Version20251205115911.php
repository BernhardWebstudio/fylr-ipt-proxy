<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205115911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dwc_identification DROP CONSTRAINT fk_9d9a7733fe472c0d');
        $this->addSql('DROP INDEX idx_9d9a7733fe472c0d');
        $this->addSql('ALTER TABLE dwc_identification DROP taxonid');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP CONSTRAINT fk_a5808a4a1d9ba2af');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP CONSTRAINT fk_a5808a4ab79b5c79');
        $this->addSql('DROP INDEX idx_a5808a4a1d9ba2af');
        $this->addSql('DROP INDEX idx_a5808a4ab79b5c79');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD occurrenceID INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ALTER resourceid TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_resource_relationship ALTER relatedresourceid TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD CONSTRAINT FK_A5808A4AB4FDDC0F FOREIGN KEY (occurrenceID) REFERENCES dwc_occurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A5808A4AB4FDDC0F ON dwc_resource_relationship (occurrenceID)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP CONSTRAINT FK_A5808A4AB4FDDC0F');
        $this->addSql('DROP INDEX IDX_A5808A4AB4FDDC0F');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP occurrenceID');
        $this->addSql('ALTER TABLE dwc_resource_relationship ALTER resourceID TYPE INT');
        $this->addSql('ALTER TABLE dwc_resource_relationship ALTER relatedResourceID TYPE INT');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD CONSTRAINT fk_a5808a4a1d9ba2af FOREIGN KEY (relatedresourceid) REFERENCES dwc_occurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD CONSTRAINT fk_a5808a4ab79b5c79 FOREIGN KEY (resourceid) REFERENCES dwc_occurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_a5808a4a1d9ba2af ON dwc_resource_relationship (relatedresourceid)');
        $this->addSql('CREATE INDEX idx_a5808a4ab79b5c79 ON dwc_resource_relationship (resourceid)');
        $this->addSql('ALTER TABLE dwc_identification ADD taxonid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD CONSTRAINT fk_9d9a7733fe472c0d FOREIGN KEY (taxonid) REFERENCES dwc_taxon (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_9d9a7733fe472c0d ON dwc_identification (taxonid)');
    }
}
