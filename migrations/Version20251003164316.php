<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003164316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE easydb_object_type (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE job_status (id SERIAL NOT NULL, user_id INT NOT NULL, job_id VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, progress INT NOT NULL, total_items INT NOT NULL, criteria JSON DEFAULT NULL, error_message TEXT DEFAULT NULL, result_file_path TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, format VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B04489DBE04EA9 ON job_status (job_id)');
        $this->addSql('CREATE INDEX IDX_2B04489DA76ED395 ON job_status (user_id)');
        $this->addSql('COMMENT ON COLUMN job_status.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN job_status.completed_at IS \'(DC2Type:datetime_immutable)\'');
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
        $this->addSql('ALTER TABLE job_status ADD CONSTRAINT FK_2B04489DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_easydb_object_type ADD CONSTRAINT FK_75D0A6E8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_easydb_object_type ADD CONSTRAINT FK_75D0A6E87F3AC098 FOREIGN KEY (easydb_object_type_id) REFERENCES easydb_object_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE job_status DROP CONSTRAINT FK_2B04489DA76ED395');
        $this->addSql('ALTER TABLE user_easydb_object_type DROP CONSTRAINT FK_75D0A6E8A76ED395');
        $this->addSql('ALTER TABLE user_easydb_object_type DROP CONSTRAINT FK_75D0A6E87F3AC098');
        $this->addSql('DROP TABLE easydb_object_type');
        $this->addSql('DROP TABLE job_status');
        $this->addSql('DROP TABLE user_easydb_object_type');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
