<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171002134647 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device_alerts DROP FOREIGN KEY FK_246AB17093035F72');
        $this->addSql('ALTER TABLE domain_alerts DROP FOREIGN KEY FK_9819CCA293035F72');
        $this->addSql('CREATE TABLE device_alert_rules (device_id INT NOT NULL, alert_rule_id INT NOT NULL, INDEX IDX_11403A1194A4C7D4 (device_id), INDEX IDX_11403A11EA1DA493 (alert_rule_id), PRIMARY KEY(device_id, alert_rule_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_alert_rules (domain_id INT NOT NULL, alert_rule_id INT NOT NULL, INDEX IDX_65E759D4115F0EE5 (domain_id), INDEX IDX_65E759D4EA1DA493 (alert_rule_id), PRIMARY KEY(domain_id, alert_rule_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alert_rule (id INT AUTO_INCREMENT NOT NULL, probe_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, datasource VARCHAR(255) NOT NULL, pattern VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C9687E485E237E06 (name), INDEX IDX_C9687E483D2D0D4A (probe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_alert_rules ADD CONSTRAINT FK_11403A1194A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_alert_rules ADD CONSTRAINT FK_11403A11EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id)');
        $this->addSql('ALTER TABLE domain_alert_rules ADD CONSTRAINT FK_65E759D4115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_alert_rules ADD CONSTRAINT FK_65E759D4EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id)');
        $this->addSql('ALTER TABLE alert_rule ADD CONSTRAINT FK_C9687E483D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id)');
        $this->addSql('DROP TABLE alert');
        $this->addSql('DROP TABLE device_alerts');
        $this->addSql('DROP TABLE domain_alerts');
        $this->addSql('ALTER TABLE slave DROP secret');
        $this->addSql('ALTER TABLE slave_group DROP secret');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device_alert_rules DROP FOREIGN KEY FK_11403A11EA1DA493');
        $this->addSql('ALTER TABLE domain_alert_rules DROP FOREIGN KEY FK_65E759D4EA1DA493');
        $this->addSql('CREATE TABLE alert (id INT AUTO_INCREMENT NOT NULL, probe_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, datasource VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, pattern VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, UNIQUE INDEX UNIQ_17FD46C15E237E06 (name), INDEX IDX_17FD46C13D2D0D4A (probe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_alerts (device_id INT NOT NULL, alert_id INT NOT NULL, INDEX IDX_246AB17094A4C7D4 (device_id), INDEX IDX_246AB17093035F72 (alert_id), PRIMARY KEY(device_id, alert_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_alerts (domain_id INT NOT NULL, alert_id INT NOT NULL, INDEX IDX_9819CCA2115F0EE5 (domain_id), INDEX IDX_9819CCA293035F72 (alert_id), PRIMARY KEY(domain_id, alert_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C13D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id)');
        $this->addSql('ALTER TABLE device_alerts ADD CONSTRAINT FK_246AB17093035F72 FOREIGN KEY (alert_id) REFERENCES alert (id)');
        $this->addSql('ALTER TABLE device_alerts ADD CONSTRAINT FK_246AB17094A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE domain_alerts ADD CONSTRAINT FK_9819CCA2115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_alerts ADD CONSTRAINT FK_9819CCA293035F72 FOREIGN KEY (alert_id) REFERENCES alert (id)');
        $this->addSql('DROP TABLE device_alert_rules');
        $this->addSql('DROP TABLE domain_alert_rules');
        $this->addSql('DROP TABLE alert_rule');
        $this->addSql('ALTER TABLE slave ADD secret VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE slave_group ADD secret VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
