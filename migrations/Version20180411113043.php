<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180411113043 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device DROP FOREIGN KEY FK_92FB68E115F0EE5');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68E115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0B727ACA70');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0B727ACA70 FOREIGN KEY (parent_id) REFERENCES domain (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE device DROP FOREIGN KEY FK_92FB68E115F0EE5');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68E115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0B727ACA70');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0B727ACA70 FOREIGN KEY (parent_id) REFERENCES domain (id)');
    }
}
