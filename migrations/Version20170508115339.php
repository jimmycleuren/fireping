<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170508115339 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE probe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, step INT NOT NULL, samples INT NOT NULL, UNIQUE INDEX UNIQ_D75E6F2A5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device (id INT AUTO_INCREMENT NOT NULL, domain_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, ip VARCHAR(255) NOT NULL, INDEX IDX_92FB68E115F0EE5 (domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_slaves (device_id INT NOT NULL, slave_id INT NOT NULL, INDEX IDX_B1C60E7B94A4C7D4 (device_id), INDEX IDX_B1C60E7B2B29BD08 (slave_id), PRIMARY KEY(device_id, slave_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_probes (device_id INT NOT NULL, probe_id INT NOT NULL, INDEX IDX_137229A994A4C7D4 (device_id), INDEX IDX_137229A93D2D0D4A (probe_id), PRIMARY KEY(device_id, probe_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE device_alerts (device_id INT NOT NULL, alert_id INT NOT NULL, INDEX IDX_246AB17094A4C7D4 (device_id), INDEX IDX_246AB17093035F72 (alert_id), PRIMARY KEY(device_id, alert_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_A7A91E0B5E237E06 (name), INDEX IDX_A7A91E0B727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_slaves (domain_id INT NOT NULL, slave_id INT NOT NULL, INDEX IDX_DB573A9115F0EE5 (domain_id), INDEX IDX_DB573A92B29BD08 (slave_id), PRIMARY KEY(domain_id, slave_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_probes (domain_id INT NOT NULL, probe_id INT NOT NULL, INDEX IDX_AF01547B115F0EE5 (domain_id), INDEX IDX_AF01547B3D2D0D4A (probe_id), PRIMARY KEY(domain_id, probe_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_alerts (domain_id INT NOT NULL, alert_id INT NOT NULL, INDEX IDX_9819CCA2115F0EE5 (domain_id), INDEX IDX_9819CCA293035F72 (alert_id), PRIMARY KEY(domain_id, alert_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slave (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, secret VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_408CF095E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alert (id INT AUTO_INCREMENT NOT NULL, probe_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, datasource VARCHAR(255) NOT NULL, pattern VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_17FD46C15E237E06 (name), INDEX IDX_17FD46C13D2D0D4A (probe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68E115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE device_slaves ADD CONSTRAINT FK_B1C60E7B94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_slaves ADD CONSTRAINT FK_B1C60E7B2B29BD08 FOREIGN KEY (slave_id) REFERENCES slave (id)');
        $this->addSql('ALTER TABLE device_probes ADD CONSTRAINT FK_137229A994A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_probes ADD CONSTRAINT FK_137229A93D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id)');
        $this->addSql('ALTER TABLE device_alerts ADD CONSTRAINT FK_246AB17094A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_alerts ADD CONSTRAINT FK_246AB17093035F72 FOREIGN KEY (alert_id) REFERENCES alert (id)');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0B727ACA70 FOREIGN KEY (parent_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_slaves ADD CONSTRAINT FK_DB573A9115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_slaves ADD CONSTRAINT FK_DB573A92B29BD08 FOREIGN KEY (slave_id) REFERENCES slave (id)');
        $this->addSql('ALTER TABLE domain_probes ADD CONSTRAINT FK_AF01547B115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_probes ADD CONSTRAINT FK_AF01547B3D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id)');
        $this->addSql('ALTER TABLE domain_alerts ADD CONSTRAINT FK_9819CCA2115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_alerts ADD CONSTRAINT FK_9819CCA293035F72 FOREIGN KEY (alert_id) REFERENCES alert (id)');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C13D2D0D4A FOREIGN KEY (probe_id) REFERENCES probe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device_probes DROP FOREIGN KEY FK_137229A93D2D0D4A');
        $this->addSql('ALTER TABLE domain_probes DROP FOREIGN KEY FK_AF01547B3D2D0D4A');
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C13D2D0D4A');
        $this->addSql('ALTER TABLE device_slaves DROP FOREIGN KEY FK_B1C60E7B94A4C7D4');
        $this->addSql('ALTER TABLE device_probes DROP FOREIGN KEY FK_137229A994A4C7D4');
        $this->addSql('ALTER TABLE device_alerts DROP FOREIGN KEY FK_246AB17094A4C7D4');
        $this->addSql('ALTER TABLE device DROP FOREIGN KEY FK_92FB68E115F0EE5');
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0B727ACA70');
        $this->addSql('ALTER TABLE domain_slaves DROP FOREIGN KEY FK_DB573A9115F0EE5');
        $this->addSql('ALTER TABLE domain_probes DROP FOREIGN KEY FK_AF01547B115F0EE5');
        $this->addSql('ALTER TABLE domain_alerts DROP FOREIGN KEY FK_9819CCA2115F0EE5');
        $this->addSql('ALTER TABLE device_slaves DROP FOREIGN KEY FK_B1C60E7B2B29BD08');
        $this->addSql('ALTER TABLE domain_slaves DROP FOREIGN KEY FK_DB573A92B29BD08');
        $this->addSql('ALTER TABLE device_alerts DROP FOREIGN KEY FK_246AB17093035F72');
        $this->addSql('ALTER TABLE domain_alerts DROP FOREIGN KEY FK_9819CCA293035F72');
        $this->addSql('DROP TABLE probe');
        $this->addSql('DROP TABLE device');
        $this->addSql('DROP TABLE device_slaves');
        $this->addSql('DROP TABLE device_probes');
        $this->addSql('DROP TABLE device_alerts');
        $this->addSql('DROP TABLE domain');
        $this->addSql('DROP TABLE domain_slaves');
        $this->addSql('DROP TABLE domain_probes');
        $this->addSql('DROP TABLE domain_alerts');
        $this->addSql('DROP TABLE slave');
        $this->addSql('DROP TABLE alert');
    }
}
