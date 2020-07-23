<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200723120027 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add email, log, slack and webhook alert destinations.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alert_destination_email (id INT NOT NULL, recipient VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alert_destination_log (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alert_destination_slack (id INT NOT NULL, url VARCHAR(255) NOT NULL, channel VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alert_destination_webhook (id INT NOT NULL, url VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alert_destination_email ADD CONSTRAINT FK_2E3E153ABF396750 FOREIGN KEY (id) REFERENCES alert_destination (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert_destination_log ADD CONSTRAINT FK_CC156325BF396750 FOREIGN KEY (id) REFERENCES alert_destination (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert_destination_slack ADD CONSTRAINT FK_1DA96D54BF396750 FOREIGN KEY (id) REFERENCES alert_destination (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert_destination_webhook ADD CONSTRAINT FK_D0E0658DBF396750 FOREIGN KEY (id) REFERENCES alert_destination (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert_destination ADD type_discriminator VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE alert_destination_email');
        $this->addSql('DROP TABLE alert_destination_log');
        $this->addSql('DROP TABLE alert_destination_slack');
        $this->addSql('DROP TABLE alert_destination_webhook');
        $this->addSql('ALTER TABLE alert_destination DROP type_discriminator');
    }
}
