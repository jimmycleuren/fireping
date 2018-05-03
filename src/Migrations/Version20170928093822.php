<?php

namespace App\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170928093822 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE device_slavegroups (device_id INT NOT NULL, slavegroup_id INT NOT NULL, INDEX IDX_C1AC34794A4C7D4 (device_id), INDEX IDX_C1AC347C29D09A3 (slavegroup_id), PRIMARY KEY(device_id, slavegroup_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_slavegroups (domain_id INT NOT NULL, slavegroup_id INT NOT NULL, INDEX IDX_78BDA082115F0EE5 (domain_id), INDEX IDX_78BDA082C29D09A3 (slavegroup_id), PRIMARY KEY(domain_id, slavegroup_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slave_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, secret VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_690F06A85E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_slavegroups ADD CONSTRAINT FK_C1AC34794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE device_slavegroups ADD CONSTRAINT FK_C1AC347C29D09A3 FOREIGN KEY (slavegroup_id) REFERENCES slave_group (id)');
        $this->addSql('ALTER TABLE domain_slavegroups ADD CONSTRAINT FK_78BDA082115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_slavegroups ADD CONSTRAINT FK_78BDA082C29D09A3 FOREIGN KEY (slavegroup_id) REFERENCES slave_group (id)');
        $this->addSql('DROP TABLE device_slaves');
        $this->addSql('DROP TABLE domain_slaves');
        $this->addSql('DROP INDEX UNIQ_408CF095E237E06 ON slave');
        $this->addSql('ALTER TABLE slave ADD slavegroup_id INT DEFAULT NULL, DROP name, CHANGE id id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE slave ADD CONSTRAINT FK_408CF09C29D09A3 FOREIGN KEY (slavegroup_id) REFERENCES slave_group (id)');
        $this->addSql('CREATE INDEX IDX_408CF09C29D09A3 ON slave (slavegroup_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device_slavegroups DROP FOREIGN KEY FK_C1AC347C29D09A3');
        $this->addSql('ALTER TABLE domain_slavegroups DROP FOREIGN KEY FK_78BDA082C29D09A3');
        $this->addSql('ALTER TABLE slave DROP FOREIGN KEY FK_408CF09C29D09A3');
        $this->addSql('CREATE TABLE device_slaves (device_id INT NOT NULL, slave_id INT NOT NULL, INDEX IDX_B1C60E7B94A4C7D4 (device_id), INDEX IDX_B1C60E7B2B29BD08 (slave_id), PRIMARY KEY(device_id, slave_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_slaves (domain_id INT NOT NULL, slave_id INT NOT NULL, INDEX IDX_DB573A9115F0EE5 (domain_id), INDEX IDX_DB573A92B29BD08 (slave_id), PRIMARY KEY(domain_id, slave_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE device_slaves ADD CONSTRAINT FK_B1C60E7B2B29BD08 FOREIGN KEY (slave_id) REFERENCES slave (id)');
        $this->addSql('ALTER TABLE device_slaves ADD CONSTRAINT FK_B1C60E7B94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE domain_slaves ADD CONSTRAINT FK_DB573A9115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_slaves ADD CONSTRAINT FK_DB573A92B29BD08 FOREIGN KEY (slave_id) REFERENCES slave (id)');
        $this->addSql('DROP TABLE device_slavegroups');
        $this->addSql('DROP TABLE domain_slavegroups');
        $this->addSql('DROP TABLE slave_group');
        $this->addSql('DROP INDEX IDX_408CF09C29D09A3 ON slave');
        $this->addSql('ALTER TABLE slave ADD name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, DROP slavegroup_id, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_408CF095E237E06 ON slave (name)');
    }
}
