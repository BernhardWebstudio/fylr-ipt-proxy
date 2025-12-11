<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211150601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_chronometric_age ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_event ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_geological_context ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_identification ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_location ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_entity ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_material_sample ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_occurrence ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_organism ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD "references" TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD modified VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD license TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD rightsHolder TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD accessRights TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD bibliographicCitation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwc_taxon ADD "references" TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dwc_organism DROP type');
        $this->addSql('ALTER TABLE dwc_organism DROP modified');
        $this->addSql('ALTER TABLE dwc_organism DROP language');
        $this->addSql('ALTER TABLE dwc_organism DROP license');
        $this->addSql('ALTER TABLE dwc_organism DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_organism DROP accessRights');
        $this->addSql('ALTER TABLE dwc_organism DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_organism DROP "references"');
        $this->addSql('ALTER TABLE dwc_identification DROP type');
        $this->addSql('ALTER TABLE dwc_identification DROP modified');
        $this->addSql('ALTER TABLE dwc_identification DROP language');
        $this->addSql('ALTER TABLE dwc_identification DROP license');
        $this->addSql('ALTER TABLE dwc_identification DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_identification DROP accessRights');
        $this->addSql('ALTER TABLE dwc_identification DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_identification DROP "references"');
        $this->addSql('ALTER TABLE dwc_location DROP type');
        $this->addSql('ALTER TABLE dwc_location DROP modified');
        $this->addSql('ALTER TABLE dwc_location DROP language');
        $this->addSql('ALTER TABLE dwc_location DROP license');
        $this->addSql('ALTER TABLE dwc_location DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_location DROP accessRights');
        $this->addSql('ALTER TABLE dwc_location DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_location DROP "references"');
        $this->addSql('ALTER TABLE dwc_occurrence DROP type');
        $this->addSql('ALTER TABLE dwc_occurrence DROP modified');
        $this->addSql('ALTER TABLE dwc_occurrence DROP language');
        $this->addSql('ALTER TABLE dwc_occurrence DROP license');
        $this->addSql('ALTER TABLE dwc_occurrence DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_occurrence DROP accessRights');
        $this->addSql('ALTER TABLE dwc_occurrence DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_occurrence DROP "references"');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP type');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP modified');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP language');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP license');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP accessRights');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_chronometric_age DROP "references"');
        $this->addSql('ALTER TABLE dwc_material_entity DROP type');
        $this->addSql('ALTER TABLE dwc_material_entity DROP modified');
        $this->addSql('ALTER TABLE dwc_material_entity DROP language');
        $this->addSql('ALTER TABLE dwc_material_entity DROP license');
        $this->addSql('ALTER TABLE dwc_material_entity DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_material_entity DROP accessRights');
        $this->addSql('ALTER TABLE dwc_material_entity DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_material_entity DROP "references"');
        $this->addSql('ALTER TABLE dwc_material_sample DROP type');
        $this->addSql('ALTER TABLE dwc_material_sample DROP modified');
        $this->addSql('ALTER TABLE dwc_material_sample DROP language');
        $this->addSql('ALTER TABLE dwc_material_sample DROP license');
        $this->addSql('ALTER TABLE dwc_material_sample DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_material_sample DROP accessRights');
        $this->addSql('ALTER TABLE dwc_material_sample DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_material_sample DROP "references"');
        $this->addSql('ALTER TABLE dwc_event DROP type');
        $this->addSql('ALTER TABLE dwc_event DROP modified');
        $this->addSql('ALTER TABLE dwc_event DROP language');
        $this->addSql('ALTER TABLE dwc_event DROP license');
        $this->addSql('ALTER TABLE dwc_event DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_event DROP accessRights');
        $this->addSql('ALTER TABLE dwc_event DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_event DROP "references"');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP type');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP modified');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP language');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP license');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP accessRights');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP "references"');
        $this->addSql('ALTER TABLE dwc_geological_context DROP type');
        $this->addSql('ALTER TABLE dwc_geological_context DROP modified');
        $this->addSql('ALTER TABLE dwc_geological_context DROP language');
        $this->addSql('ALTER TABLE dwc_geological_context DROP license');
        $this->addSql('ALTER TABLE dwc_geological_context DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_geological_context DROP accessRights');
        $this->addSql('ALTER TABLE dwc_geological_context DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_geological_context DROP "references"');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP type');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP modified');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP language');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP license');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP accessRights');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP "references"');
        $this->addSql('ALTER TABLE dwc_taxon DROP type');
        $this->addSql('ALTER TABLE dwc_taxon DROP modified');
        $this->addSql('ALTER TABLE dwc_taxon DROP language');
        $this->addSql('ALTER TABLE dwc_taxon DROP license');
        $this->addSql('ALTER TABLE dwc_taxon DROP rightsHolder');
        $this->addSql('ALTER TABLE dwc_taxon DROP accessRights');
        $this->addSql('ALTER TABLE dwc_taxon DROP bibliographicCitation');
        $this->addSql('ALTER TABLE dwc_taxon DROP "references"');
    }
}
