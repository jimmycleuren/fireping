<?php

namespace App\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171106124456 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE alert_rule ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE alert_rule ADD CONSTRAINT FK_C9687E48727ACA70 FOREIGN KEY (parent_id) REFERENCES alert_rule (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C9687E48727ACA70 ON alert_rule (parent_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE alert_rule DROP FOREIGN KEY FK_C9687E48727ACA70');
        $this->addSql('DROP INDEX UNIQ_C9687E48727ACA70 ON alert_rule');
        $this->addSql('ALTER TABLE alert_rule DROP parent_id');
    }
}
