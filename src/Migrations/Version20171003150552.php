<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171003150552 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE alert (id INT AUTO_INCREMENT NOT NULL, device_id INT DEFAULT NULL, alert_rule_id INT DEFAULT NULL, slave_group_id INT DEFAULT NULL, active INT NOT NULL, firstseen DATETIME NOT NULL, lastseen DATETIME NOT NULL, INDEX IDX_17FD46C194A4C7D4 (device_id), INDEX IDX_17FD46C1EA1DA493 (alert_rule_id), INDEX IDX_17FD46C177CF7396 (slave_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C194A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id)');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C177CF7396 FOREIGN KEY (slave_group_id) REFERENCES slave_group (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE alert');
    }
}
