<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190917113724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device_slavegroups DROP FOREIGN KEY FK_C1AC34794A4C7D4');
        $this->addSql('ALTER TABLE device_slavegroups ADD CONSTRAINT FK_C1AC34794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device_slavegroups DROP FOREIGN KEY FK_C1AC34794A4C7D4');
        $this->addSql('ALTER TABLE device_slavegroups ADD CONSTRAINT FK_C1AC34794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id)');
    }
}
