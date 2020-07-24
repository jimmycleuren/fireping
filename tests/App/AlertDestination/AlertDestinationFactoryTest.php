<?php

namespace Tests\App\AlertDestination;

use App\AlertDestination\AlertDestinationFactory;
use App\AlertDestination\Http;
use App\AlertDestination\Monolog;
use App\Entity\AlertDestination\EmailDestination;
use App\Entity\AlertDestination\LogDestination;
use App\Entity\AlertDestination\SlackDestination;
use App\Entity\AlertDestination\WebhookDestination;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertDestinationFactoryTest extends WebTestCase
{
    public function testCreateHttp(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new WebhookDestination();
        $alertDestination->setUrl('https://example.tld');
        $http = $factory->create($alertDestination);

        self::assertEquals(Http::class, get_class($http));
    }

    public function testCreateMonolog(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new LogDestination();
        $monolog = $factory->create($alertDestination);

        self::assertEquals(Monolog::class, get_class($monolog));
    }

    public function testCreateEmail(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new EmailDestination();
        $alertDestination->setRecipient('user@fireping.example');
        $monolog = $factory->create($alertDestination);

        self::assertEquals(EmailDestination::class, get_class($monolog));
    }

    public function testCreateSlack(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new SlackDestination();
        $alertDestination->setUrl('https://slack.example');
        $alertDestination->setChannel('channel');
        $monolog = $factory->create($alertDestination);

        self::assertEquals(\App\AlertDestination\Slack::class, get_class($monolog));
    }
}
