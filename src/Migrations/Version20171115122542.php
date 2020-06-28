<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171115122542 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C177CF7396');
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C194A4C7D4');
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1EA1DA493');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C177CF7396 FOREIGN KEY (slave_group_id) REFERENCES slave_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C194A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C194A4C7D4');
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1EA1DA493');
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C177CF7396');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C194A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1EA1DA493 FOREIGN KEY (alert_rule_id) REFERENCES alert_rule (id)');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C177CF7396 FOREIGN KEY (slave_group_id) REFERENCES slave_group (id)');
    }
}
