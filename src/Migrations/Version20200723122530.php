<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200723122530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate old AlertDestinations and then remove type and parameters columns from alert_destination table.';
    }

    public function preUp(Schema $schema): void
    {
        parent::preUp($schema);

        $destinations = $this->connection->prepare('SELECT * FROM alert_destination;');
        $destinations->execute();

        foreach ($destinations as $destination) {
            if ($destination['type_discriminator'] !== '' || $destination['type'] === '') {
                continue;
            }

            if ($destination['type'] === 'monolog') {
                $insertMonolog = $this->connection->prepare('INSERT INTO alert_destination_log (`id`) VALUES (:id);');
                $insertMonolog->bindParam('id', $destination['id']);
                $insertMonolog->execute();
            }

            $parameters = json_decode($destination['parameters'], true) ?? [];

            if ($destination['type'] === 'http') {
                $insertHttp = $this->connection->prepare('INSERT INTO alert_destination_webhook (`id`, `url`) VALUES (:id, :url);');
                $insertHttp->bindParam('id', $destination['id']);
                $url = $parameters['url'] ?? '';
                $insertHttp->bindParam('url', $url);
                $insertHttp->execute();
            }

            if ($destination['type'] === 'slack') {
                $insertHttp = $this->connection->prepare('INSERT INTO alert_destination_slack (`id`, `url`, `channel`) VALUES (:id, :url, :channel);');
                $insertHttp->bindParam('id', $destination['id']);
                $url = $parameters['url'] ?? '';
                $insertHttp->bindParam('url', $url);
                $channel = $parameters['channel'] ?? '';
                $insertHttp->bindParam('channel', $channel);
                $insertHttp->execute();
            }

            if ($destination['type'] === 'mail') {
                $insertHttp = $this->connection->prepare('INSERT INTO alert_destination_email (`id`, `recipient`) VALUES (:id, :recipient);');
                $insertHttp->bindParam('id', $destination['id']);
                $recipient = $parameters['recipient'] ?? '';
                $insertHttp->bindParam('recipient', $recipient);
                $insertHttp->execute();
            }

            $map = [
                'monolog' => 'monolog',
                'http' => 'webhook',
                'slack' => 'slack',
                'mail' => 'email'
            ];

            if (\in_array($destination['type'], ['monolog', 'http', 'slack', 'mail'], true)) {
                $setDiscriminator = $this->connection->prepare('UPDATE alert_destination SET `type_discriminator` = :type WHERE `id` = :id');
                $setDiscriminator->bindParam('type', $map[$destination['type']]);
                $setDiscriminator->bindParam('id', $destination['id']);
                $setDiscriminator->execute();
            }
        }
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_destination DROP type, DROP parameters');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_destination ADD type VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, ADD parameters LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:json)\'');
    }
}
