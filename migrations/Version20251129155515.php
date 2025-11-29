<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129155515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dwc_chronometric_age (id SERIAL NOT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE dwc_event (id SERIAL NOT NULL, eventID VARCHAR(255) NOT NULL, parentEventID VARCHAR(255) DEFAULT NULL, eventType VARCHAR(255) DEFAULT NULL, fieldNumber VARCHAR(255) DEFAULT NULL, eventDate VARCHAR(255) DEFAULT NULL, eventTime TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, startDayOfYear INT DEFAULT NULL, endDayOfYear INT DEFAULT NULL, year VARCHAR(255) DEFAULT NULL, month VARCHAR(255) DEFAULT NULL, day VARCHAR(255) DEFAULT NULL, verbatimEventDate VARCHAR(255) DEFAULT NULL, habitat VARCHAR(255) DEFAULT NULL, samplingProtocol VARCHAR(255) DEFAULT NULL, sampleSizeValue VARCHAR(255) DEFAULT NULL, sampleSizeUnit VARCHAR(255) DEFAULT NULL, samplingEffort VARCHAR(255) DEFAULT NULL, fieldNotes VARCHAR(255) DEFAULT NULL, eventRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, locationID INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FB194D6F10409BA4 ON dwc_event (eventID)');
        $this->addSql('CREATE INDEX IDX_FB194D6FADB908A5 ON dwc_event (locationID)');
        $this->addSql('CREATE TABLE dwc_geological_context (id SERIAL NOT NULL, geologicalContextID VARCHAR(255) NOT NULL, earliestEonOrLowestEonothem VARCHAR(255) DEFAULT NULL, latestEonOrHighestEonothem VARCHAR(255) DEFAULT NULL, earliestEraOrLowestErathem VARCHAR(255) DEFAULT NULL, latestEraOrHighestErathem VARCHAR(255) DEFAULT NULL, earliestPeriodOrLowestSystem VARCHAR(255) DEFAULT NULL, latestPeriodOrHighestSystem VARCHAR(255) DEFAULT NULL, earliestEpochOrLowestSeries VARCHAR(255) DEFAULT NULL, latestEpochOrHighestSeries VARCHAR(255) DEFAULT NULL, earliestAgeOrLowestStage VARCHAR(255) DEFAULT NULL, latestAgeOrHighestStage VARCHAR(255) DEFAULT NULL, lowestBiostratigraphicZone VARCHAR(255) DEFAULT NULL, highestBiostratigraphicZone VARCHAR(255) DEFAULT NULL, lithostratigraphicTerms VARCHAR(255) DEFAULT NULL, "group" VARCHAR(255) DEFAULT NULL, formation VARCHAR(255) DEFAULT NULL, member VARCHAR(255) DEFAULT NULL, bed VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F9840BA3F9471F59 ON dwc_geological_context (geologicalContextID)');
        $this->addSql('CREATE TABLE dwc_identification (id SERIAL NOT NULL, identificationID VARCHAR(255) NOT NULL, verbatimIdentification VARCHAR(255) DEFAULT NULL, identificationQualifier VARCHAR(255) DEFAULT NULL, typeStatus VARCHAR(255) DEFAULT NULL, identifiedBy VARCHAR(255) DEFAULT NULL, identifiedByID VARCHAR(255) DEFAULT NULL, dateIdentified VARCHAR(255) DEFAULT NULL, identificationReferences VARCHAR(255) DEFAULT NULL, identificationVerificationStatus VARCHAR(255) DEFAULT NULL, identificationRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, taxonID INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D9A77331A764480 ON dwc_identification (identificationID)');
        $this->addSql('CREATE INDEX IDX_9D9A7733FE472C0D ON dwc_identification (taxonID)');
        $this->addSql('CREATE TABLE dwc_location (id SERIAL NOT NULL, locationID VARCHAR(255) NOT NULL, higherGeographyID VARCHAR(255) DEFAULT NULL, higherGeography VARCHAR(255) DEFAULT NULL, continent VARCHAR(255) DEFAULT NULL, waterBody VARCHAR(255) DEFAULT NULL, islandGroup VARCHAR(255) DEFAULT NULL, island VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, countryCode VARCHAR(255) DEFAULT NULL, stateProvince VARCHAR(255) DEFAULT NULL, county VARCHAR(255) DEFAULT NULL, municipality VARCHAR(255) DEFAULT NULL, locality VARCHAR(255) DEFAULT NULL, verbatimLocality VARCHAR(255) DEFAULT NULL, minimumElevationInMeters DOUBLE PRECISION DEFAULT NULL, maximumElevationInMeters DOUBLE PRECISION DEFAULT NULL, verbatimElevation VARCHAR(255) DEFAULT NULL, verticalDatum VARCHAR(255) DEFAULT NULL, minimumDepthInMeters DOUBLE PRECISION DEFAULT NULL, maximumDepthInMeters DOUBLE PRECISION DEFAULT NULL, verbatimDepth VARCHAR(255) DEFAULT NULL, minimumDistanceAboveSurfaceInMeters DOUBLE PRECISION DEFAULT NULL, maximumDistanceAboveSurfaceInMeters DOUBLE PRECISION DEFAULT NULL, locationAccordingTo VARCHAR(255) DEFAULT NULL, locationRemarks VARCHAR(255) DEFAULT NULL, decimalLatitude DOUBLE PRECISION DEFAULT NULL, decimalLongitude DOUBLE PRECISION DEFAULT NULL, geodeticDatum VARCHAR(255) DEFAULT NULL, coordinateUncertaintyInMeters DOUBLE PRECISION DEFAULT NULL, coordinatePrecision VARCHAR(255) DEFAULT NULL, pointRadiusSpatialFit VARCHAR(255) DEFAULT NULL, verbatimCoordinates VARCHAR(255) DEFAULT NULL, verbatimLatitude VARCHAR(255) DEFAULT NULL, verbatimLongitude VARCHAR(255) DEFAULT NULL, verbatimCoordinateSystem VARCHAR(255) DEFAULT NULL, verbatimSRS VARCHAR(255) DEFAULT NULL, footprintWKT VARCHAR(255) DEFAULT NULL, footprintSRS VARCHAR(255) DEFAULT NULL, footprintSpatialFit VARCHAR(255) DEFAULT NULL, georeferencedBy VARCHAR(255) DEFAULT NULL, georeferencedDate VARCHAR(255) DEFAULT NULL, georeferenceProtocol VARCHAR(255) DEFAULT NULL, georeferenceSources VARCHAR(255) DEFAULT NULL, georeferenceRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, geologicalContextID INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B1FB11EADB908A5 ON dwc_location (locationID)');
        $this->addSql('CREATE INDEX IDX_2B1FB11EF9471F59 ON dwc_location (geologicalContextID)');
        $this->addSql('CREATE TABLE dwc_material_entity (id SERIAL NOT NULL, materialEntityID VARCHAR(255) NOT NULL, preparations VARCHAR(255) DEFAULT NULL, disposition VARCHAR(255) DEFAULT NULL, verbatimLabel VARCHAR(255) DEFAULT NULL, associatedSequences VARCHAR(255) DEFAULT NULL, materialEntityRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, materialSampleID INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5EAFAFFDF1E251B0 ON dwc_material_entity (materialEntityID)');
        $this->addSql('CREATE INDEX IDX_5EAFAFFD74C69A31 ON dwc_material_entity (materialSampleID)');
        $this->addSql('CREATE TABLE dwc_material_sample (id SERIAL NOT NULL, materialSampleID VARCHAR(255) NOT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A18C9D5674C69A31 ON dwc_material_sample (materialSampleID)');
        $this->addSql('CREATE TABLE dwc_measurement_or_fact (id SERIAL NOT NULL, measurementID VARCHAR(255) NOT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, occurrenceID INT DEFAULT NULL, eventID INT DEFAULT NULL, locationID INT DEFAULT NULL, taxonID INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C69DA8BCA02362C ON dwc_measurement_or_fact (measurementID)');
        $this->addSql('CREATE INDEX IDX_8C69DA8BB4FDDC0F ON dwc_measurement_or_fact (occurrenceID)');
        $this->addSql('CREATE INDEX IDX_8C69DA8B10409BA4 ON dwc_measurement_or_fact (eventID)');
        $this->addSql('CREATE INDEX IDX_8C69DA8BADB908A5 ON dwc_measurement_or_fact (locationID)');
        $this->addSql('CREATE INDEX IDX_8C69DA8BFE472C0D ON dwc_measurement_or_fact (taxonID)');
        $this->addSql('CREATE TABLE dwc_occurrence (id SERIAL NOT NULL, occurrenceID VARCHAR(255) NOT NULL, catalogNumber VARCHAR(255) DEFAULT NULL, recordNumber VARCHAR(255) DEFAULT NULL, recordedBy VARCHAR(255) DEFAULT NULL, recordedByID VARCHAR(255) DEFAULT NULL, individualCount INT DEFAULT NULL, organismQuantity VARCHAR(255) DEFAULT NULL, organismQuantityType VARCHAR(255) DEFAULT NULL, sex VARCHAR(255) DEFAULT NULL, lifeStage VARCHAR(255) DEFAULT NULL, reproductiveCondition VARCHAR(255) DEFAULT NULL, caste VARCHAR(255) DEFAULT NULL, behavior VARCHAR(255) DEFAULT NULL, vitality VARCHAR(255) DEFAULT NULL, establishmentMeans VARCHAR(255) DEFAULT NULL, degreeOfEstablishment VARCHAR(255) DEFAULT NULL, pathway VARCHAR(255) DEFAULT NULL, georeferenceVerificationStatus VARCHAR(255) DEFAULT NULL, occurrenceStatus VARCHAR(255) DEFAULT NULL, associatedMedia VARCHAR(255) DEFAULT NULL, associatedOccurrences VARCHAR(255) DEFAULT NULL, associatedReferences VARCHAR(255) DEFAULT NULL, associatedTaxa VARCHAR(255) DEFAULT NULL, otherCatalogNumbers VARCHAR(255) DEFAULT NULL, occurrenceRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, organismID INT DEFAULT NULL, materialEntityID INT DEFAULT NULL, eventID INT DEFAULT NULL, locationID INT DEFAULT NULL, identificationID INT DEFAULT NULL, taxonID INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6AB07DB6B4FDDC0F ON dwc_occurrence (occurrenceID)');
        $this->addSql('CREATE INDEX IDX_6AB07DB6258F6FF9 ON dwc_occurrence (organismID)');
        $this->addSql('CREATE INDEX IDX_6AB07DB6F1E251B0 ON dwc_occurrence (materialEntityID)');
        $this->addSql('CREATE INDEX IDX_6AB07DB610409BA4 ON dwc_occurrence (eventID)');
        $this->addSql('CREATE INDEX IDX_6AB07DB6ADB908A5 ON dwc_occurrence (locationID)');
        $this->addSql('CREATE INDEX IDX_6AB07DB61A764480 ON dwc_occurrence (identificationID)');
        $this->addSql('CREATE INDEX IDX_6AB07DB6FE472C0D ON dwc_occurrence (taxonID)');
        $this->addSql('CREATE TABLE dwc_organism (id SERIAL NOT NULL, organismID VARCHAR(255) NOT NULL, organismName VARCHAR(255) DEFAULT NULL, organismScope VARCHAR(255) DEFAULT NULL, associatedOrganisms VARCHAR(255) DEFAULT NULL, previousIdentifications VARCHAR(255) DEFAULT NULL, organismRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_78D2B2F9258F6FF9 ON dwc_organism (organismID)');
        $this->addSql('CREATE TABLE dwc_resource_relationship (id SERIAL NOT NULL, resourceRelationshipID VARCHAR(255) NOT NULL, relationshipOfResourceID VARCHAR(255) DEFAULT NULL, relationshipOfResource VARCHAR(255) DEFAULT NULL, relationshipAccordingTo VARCHAR(255) DEFAULT NULL, relationshipEstablishedDate VARCHAR(255) DEFAULT NULL, relationshipRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, resourceID INT DEFAULT NULL, relatedResourceID INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A5808A4AE5BD646E ON dwc_resource_relationship (resourceRelationshipID)');
        $this->addSql('CREATE INDEX IDX_A5808A4AB79B5C79 ON dwc_resource_relationship (resourceID)');
        $this->addSql('CREATE INDEX IDX_A5808A4A1D9BA2AF ON dwc_resource_relationship (relatedResourceID)');
        $this->addSql('CREATE TABLE dwc_taxon (id SERIAL NOT NULL, taxonID VARCHAR(255) NOT NULL, scientificNameID VARCHAR(255) DEFAULT NULL, acceptedNameUsageID VARCHAR(255) DEFAULT NULL, parentNameUsageID VARCHAR(255) DEFAULT NULL, originalNameUsageID VARCHAR(255) DEFAULT NULL, nameAccordingToID VARCHAR(255) DEFAULT NULL, namePublishedInID VARCHAR(255) DEFAULT NULL, taxonConceptID VARCHAR(255) DEFAULT NULL, scientificName VARCHAR(255) DEFAULT NULL, acceptedNameUsage VARCHAR(255) DEFAULT NULL, parentNameUsage VARCHAR(255) DEFAULT NULL, originalNameUsage VARCHAR(255) DEFAULT NULL, nameAccordingTo VARCHAR(255) DEFAULT NULL, namePublishedIn VARCHAR(255) DEFAULT NULL, namePublishedInYear VARCHAR(255) DEFAULT NULL, higherClassification VARCHAR(255) DEFAULT NULL, kingdom VARCHAR(255) DEFAULT NULL, phylum VARCHAR(255) DEFAULT NULL, "class" VARCHAR(255) DEFAULT NULL, "order" VARCHAR(255) DEFAULT NULL, superfamily VARCHAR(255) DEFAULT NULL, family VARCHAR(255) DEFAULT NULL, subfamily VARCHAR(255) DEFAULT NULL, tribe VARCHAR(255) DEFAULT NULL, subtribe VARCHAR(255) DEFAULT NULL, genus VARCHAR(255) DEFAULT NULL, genericName VARCHAR(255) DEFAULT NULL, subgenus VARCHAR(255) DEFAULT NULL, infragenericEpithet VARCHAR(255) DEFAULT NULL, specificEpithet VARCHAR(255) DEFAULT NULL, infraspecificEpithet VARCHAR(255) DEFAULT NULL, cultivarEpithet VARCHAR(255) DEFAULT NULL, taxonRank VARCHAR(255) DEFAULT NULL, verbatimTaxonRank VARCHAR(255) DEFAULT NULL, scientificNameAuthorship VARCHAR(255) DEFAULT NULL, vernacularName VARCHAR(255) DEFAULT NULL, nomenclaturalCode VARCHAR(255) DEFAULT NULL, taxonomicStatus VARCHAR(255) DEFAULT NULL, nomenclaturalStatus VARCHAR(255) DEFAULT NULL, taxonRemarks VARCHAR(255) DEFAULT NULL, institutionID VARCHAR(255) DEFAULT NULL, collectionID VARCHAR(255) DEFAULT NULL, datasetID VARCHAR(255) DEFAULT NULL, institutionCode VARCHAR(255) DEFAULT NULL, collectionCode VARCHAR(255) DEFAULT NULL, datasetName VARCHAR(255) DEFAULT NULL, ownerInstitutionCode VARCHAR(255) DEFAULT NULL, basisOfRecord VARCHAR(255) DEFAULT NULL, informationWithheld VARCHAR(255) DEFAULT NULL, dataGeneralizations VARCHAR(255) DEFAULT NULL, dynamicProperties VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BD06463FE472C0D ON dwc_taxon (taxonID)');
        $this->addSql('CREATE TABLE easydb_object_type (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE job_status (id SERIAL NOT NULL, user_id INT NOT NULL, job_id VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, progress INT NOT NULL, total_items INT NOT NULL, criteria JSON DEFAULT NULL, error_message TEXT DEFAULT NULL, result_file_path TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, format VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B04489DBE04EA9 ON job_status (job_id)');
        $this->addSql('CREATE INDEX IDX_2B04489DA76ED395 ON job_status (user_id)');
        $this->addSql('COMMENT ON COLUMN job_status.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN job_status.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE occurrence_import (id SERIAL NOT NULL, manual_import_trigger_id INT DEFAULT NULL, occurrence_id INT NOT NULL, first_imported_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, global_object_id VARCHAR(255) NOT NULL, tag_id INT DEFAULT NULL, object_type VARCHAR(255) DEFAULT NULL, remote_last_updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A90E63E26FB1C59 ON occurrence_import (manual_import_trigger_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A90E63E230572FAC ON occurrence_import (occurrence_id)');
        $this->addSql('COMMENT ON COLUMN occurrence_import.first_imported_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN occurrence_import.last_updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN occurrence_import.remote_last_updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON "user" (username)');
        $this->addSql('CREATE TABLE user_easydb_object_type (user_id INT NOT NULL, easydb_object_type_id INT NOT NULL, PRIMARY KEY(user_id, easydb_object_type_id))');
        $this->addSql('CREATE INDEX IDX_75D0A6E8A76ED395 ON user_easydb_object_type (user_id)');
        $this->addSql('CREATE INDEX IDX_75D0A6E87F3AC098 ON user_easydb_object_type (easydb_object_type_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE dwc_event ADD CONSTRAINT FK_FB194D6FADB908A5 FOREIGN KEY (locationID) REFERENCES dwc_location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_identification ADD CONSTRAINT FK_9D9A7733FE472C0D FOREIGN KEY (taxonID) REFERENCES dwc_taxon (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_location ADD CONSTRAINT FK_2B1FB11EF9471F59 FOREIGN KEY (geologicalContextID) REFERENCES dwc_geological_context (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_material_entity ADD CONSTRAINT FK_5EAFAFFD74C69A31 FOREIGN KEY (materialSampleID) REFERENCES dwc_material_sample (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD CONSTRAINT FK_8C69DA8BB4FDDC0F FOREIGN KEY (occurrenceID) REFERENCES dwc_occurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD CONSTRAINT FK_8C69DA8B10409BA4 FOREIGN KEY (eventID) REFERENCES dwc_event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD CONSTRAINT FK_8C69DA8BADB908A5 FOREIGN KEY (locationID) REFERENCES dwc_location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact ADD CONSTRAINT FK_8C69DA8BFE472C0D FOREIGN KEY (taxonID) REFERENCES dwc_taxon (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_occurrence ADD CONSTRAINT FK_6AB07DB6258F6FF9 FOREIGN KEY (organismID) REFERENCES dwc_organism (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_occurrence ADD CONSTRAINT FK_6AB07DB6F1E251B0 FOREIGN KEY (materialEntityID) REFERENCES dwc_material_entity (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_occurrence ADD CONSTRAINT FK_6AB07DB610409BA4 FOREIGN KEY (eventID) REFERENCES dwc_event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_occurrence ADD CONSTRAINT FK_6AB07DB6ADB908A5 FOREIGN KEY (locationID) REFERENCES dwc_location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_occurrence ADD CONSTRAINT FK_6AB07DB61A764480 FOREIGN KEY (identificationID) REFERENCES dwc_identification (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_occurrence ADD CONSTRAINT FK_6AB07DB6FE472C0D FOREIGN KEY (taxonID) REFERENCES dwc_taxon (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD CONSTRAINT FK_A5808A4AB79B5C79 FOREIGN KEY (resourceID) REFERENCES dwc_occurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dwc_resource_relationship ADD CONSTRAINT FK_A5808A4A1D9BA2AF FOREIGN KEY (relatedResourceID) REFERENCES dwc_occurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE job_status ADD CONSTRAINT FK_2B04489DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE occurrence_import ADD CONSTRAINT FK_A90E63E26FB1C59 FOREIGN KEY (manual_import_trigger_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE occurrence_import ADD CONSTRAINT FK_A90E63E230572FAC FOREIGN KEY (occurrence_id) REFERENCES dwc_occurrence (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_easydb_object_type ADD CONSTRAINT FK_75D0A6E8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_easydb_object_type ADD CONSTRAINT FK_75D0A6E87F3AC098 FOREIGN KEY (easydb_object_type_id) REFERENCES easydb_object_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dwc_event DROP CONSTRAINT FK_FB194D6FADB908A5');
        $this->addSql('ALTER TABLE dwc_identification DROP CONSTRAINT FK_9D9A7733FE472C0D');
        $this->addSql('ALTER TABLE dwc_location DROP CONSTRAINT FK_2B1FB11EF9471F59');
        $this->addSql('ALTER TABLE dwc_material_entity DROP CONSTRAINT FK_5EAFAFFD74C69A31');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP CONSTRAINT FK_8C69DA8BB4FDDC0F');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP CONSTRAINT FK_8C69DA8B10409BA4');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP CONSTRAINT FK_8C69DA8BADB908A5');
        $this->addSql('ALTER TABLE dwc_measurement_or_fact DROP CONSTRAINT FK_8C69DA8BFE472C0D');
        $this->addSql('ALTER TABLE dwc_occurrence DROP CONSTRAINT FK_6AB07DB6258F6FF9');
        $this->addSql('ALTER TABLE dwc_occurrence DROP CONSTRAINT FK_6AB07DB6F1E251B0');
        $this->addSql('ALTER TABLE dwc_occurrence DROP CONSTRAINT FK_6AB07DB610409BA4');
        $this->addSql('ALTER TABLE dwc_occurrence DROP CONSTRAINT FK_6AB07DB6ADB908A5');
        $this->addSql('ALTER TABLE dwc_occurrence DROP CONSTRAINT FK_6AB07DB61A764480');
        $this->addSql('ALTER TABLE dwc_occurrence DROP CONSTRAINT FK_6AB07DB6FE472C0D');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP CONSTRAINT FK_A5808A4AB79B5C79');
        $this->addSql('ALTER TABLE dwc_resource_relationship DROP CONSTRAINT FK_A5808A4A1D9BA2AF');
        $this->addSql('ALTER TABLE job_status DROP CONSTRAINT FK_2B04489DA76ED395');
        $this->addSql('ALTER TABLE occurrence_import DROP CONSTRAINT FK_A90E63E26FB1C59');
        $this->addSql('ALTER TABLE occurrence_import DROP CONSTRAINT FK_A90E63E230572FAC');
        $this->addSql('ALTER TABLE user_easydb_object_type DROP CONSTRAINT FK_75D0A6E8A76ED395');
        $this->addSql('ALTER TABLE user_easydb_object_type DROP CONSTRAINT FK_75D0A6E87F3AC098');
        $this->addSql('DROP TABLE dwc_chronometric_age');
        $this->addSql('DROP TABLE dwc_event');
        $this->addSql('DROP TABLE dwc_geological_context');
        $this->addSql('DROP TABLE dwc_identification');
        $this->addSql('DROP TABLE dwc_location');
        $this->addSql('DROP TABLE dwc_material_entity');
        $this->addSql('DROP TABLE dwc_material_sample');
        $this->addSql('DROP TABLE dwc_measurement_or_fact');
        $this->addSql('DROP TABLE dwc_occurrence');
        $this->addSql('DROP TABLE dwc_organism');
        $this->addSql('DROP TABLE dwc_resource_relationship');
        $this->addSql('DROP TABLE dwc_taxon');
        $this->addSql('DROP TABLE easydb_object_type');
        $this->addSql('DROP TABLE job_status');
        $this->addSql('DROP TABLE occurrence_import');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_easydb_object_type');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
