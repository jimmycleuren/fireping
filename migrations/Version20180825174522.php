<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180825174522 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device ADD storage_node_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68E3131ACB5 FOREIGN KEY (storage_node_id) REFERENCES storage_node (id)');
        $this->addSql('CREATE INDEX IDX_92FB68E3131ACB5 ON device (storage_node_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device DROP FOREIGN KEY FK_92FB68E3131ACB5');
        $this->addSql('DROP INDEX IDX_92FB68E3131ACB5 ON device');
        $this->addSql('ALTER TABLE device DROP storage_node_id');
    }
}
