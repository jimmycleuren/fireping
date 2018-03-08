<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180308174502 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE alert_destination (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, parameters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_B894E3165E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_alert_destinations (device_id INT NOT NULL, alert_destination_id INT NOT NULL, INDEX IDX_A760D66C94A4C7D4 (device_id), INDEX IDX_A760D66CFCDBED38 (alert_destination_id), PRIMARY KEY(device_id, alert_destination_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_alert_destinations (domain_id INT NOT NULL, alert_destination_id INT NOT NULL, INDEX IDX_917F6FF9115F0EE5 (domain_id), INDEX IDX_917F6FF9FCDBED38 (alert_destination_id), PRIMARY KEY(domain_id, alert_destination_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_alert_destinations ADD CONSTRAINT FK_A760D66C94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_alert_destinations ADD CONSTRAINT FK_A760D66CFCDBED38 FOREIGN KEY (alert_destination_id) REFERENCES alert_destination (id)');
        $this->addSql('ALTER TABLE domain_alert_destinations ADD CONSTRAINT FK_917F6FF9115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_alert_destinations ADD CONSTRAINT FK_917F6FF9FCDBED38 FOREIGN KEY (alert_destination_id) REFERENCES alert_destination (id)');
        $this->addSql('CREATE INDEX slaveresult ON alert (device_id, alert_rule_id, slave_group_id, active)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device_alert_destinations DROP FOREIGN KEY FK_A760D66CFCDBED38');
        $this->addSql('ALTER TABLE domain_alert_destinations DROP FOREIGN KEY FK_917F6FF9FCDBED38');
        $this->addSql('DROP TABLE alert_destination');
        $this->addSql('DROP TABLE device_alert_destinations');
        $this->addSql('DROP TABLE domain_alert_destinations');
        $this->addSql('DROP INDEX slaveresult ON alert');
    }
}
