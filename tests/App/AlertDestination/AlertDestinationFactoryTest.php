<?php

namespace Tests\App\AlertDestination;

use App\AlertDestination\AlertDestinationFactory;
use App\AlertDestination\Http;
use App\AlertDestination\Monolog;
use App\Entity\AlertDestination\Email;
use App\Entity\AlertDestination\Logging;
use App\Entity\AlertDestination\Slack;
use App\Entity\AlertDestination\Webhook;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertDestinationFactoryTest extends WebTestCase
{
    public function testCreateHttp(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new Webhook();
        $alertDestination->setUrl('https://example.tld');
        $http = $factory->create($alertDestination);

        self::assertEquals(Http::class, get_class($http));
    }

    public function testCreateMonolog(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new Logging();
        $monolog = $factory->create($alertDestination);

        self::assertEquals(Monolog::class, get_class($monolog));
    }

    public function testCreateEmail(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new Email();
        $alertDestination->setRecipient('user@fireping.example');
        $monolog = $factory->create($alertDestination);

        self::assertEquals(Email::class, get_class($monolog));
    }

    public function testCreateSlack(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new Slack();
        $alertDestination->setUrl('https://slack.example');
        $alertDestination->setChannel('channel');
        $monolog = $factory->create($alertDestination);

        self::assertEquals(\App\AlertDestination\Slack::class, get_class($monolog));
    }
}
