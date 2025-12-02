<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202092045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dwc_chronometric_age ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_event ALTER verbatimeventdate TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_event ALTER fieldnotes TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_event ALTER eventremarks TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_event ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_geological_context ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_identification ALTER identificationremarks TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_identification ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimlocality TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimelevation TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimdepth TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER locationremarks TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimcoordinates TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimlatitude TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimlongitude TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimcoordinatesystem TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimsrs TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER georeferenceremarks TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_location ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_material_entity ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_material_sample ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedmedia TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedoccurrences TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedreferences TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedtaxa TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER othercatalognumbers TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER occurrenceremarks TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_organism ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_resource_relationship ALTER dynamicproperties TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_taxon ALTER taxonremarks TYPE TEXT');
        $this->addSql('ALTER TABLE dwc_taxon ALTER dynamicproperties TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_organism ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimLocality TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimElevation TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimDepth TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER locationRemarks TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimCoordinates TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimLatitude TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimLongitude TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimCoordinateSystem TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER verbatimSRS TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER georeferenceRemarks TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_location ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_taxon ALTER taxonRemarks TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_taxon ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_resource_relationship ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_chronometric_age ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_identification ALTER identificationRemarks TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_identification ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_material_sample ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_material_entity ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_geological_context ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_event ALTER verbatimEventDate TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_event ALTER fieldNotes TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_event ALTER eventRemarks TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_event ALTER dynamicProperties TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedMedia TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedOccurrences TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedReferences TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER associatedTaxa TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER otherCatalogNumbers TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER occurrenceRemarks TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dwc_occurrence ALTER dynamicProperties TYPE VARCHAR(255)');
    }
}
