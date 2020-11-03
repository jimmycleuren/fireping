<?php

namespace Tests\App\AlertDestination;

use App\AlertDestination\AlertDestinationFactory;
use App\AlertDestination\Http;
use App\AlertDestination\Mail;
use App\AlertDestination\Monolog;
use App\AlertDestination\Slack;
use App\Entity\AlertDestination\AlertDestination;
use App\Entity\AlertDestination\EmailDestination;
use App\Entity\AlertDestination\LogDestination;
use App\Entity\AlertDestination\SlackDestination;
use App\Entity\AlertDestination\WebhookDestination;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertInstanceOf;

class AlertDestinationFactoryTest extends WebTestCase
{
    /**
     * @dataProvider destinationProvider
     */
    public function testFactoryCreatesHandlers(AlertDestination $destination, string $expectedHandlerClassName): void
    {
        $factory = new AlertDestinationFactory(static::createClient()->getContainer());
        $handler = $factory->create($destination);

        self::assertInstanceOf($expectedHandlerClassName, $handler);
    }

    public function destinationProvider(): iterable
    {
        yield [new LogDestination(), Monolog::class];

        $webhook = new WebhookDestination();
        $webhook->setUrl('https://example.tld');

        yield [$webhook, Http::class];

        $email = new EmailDestination();
        $email->setRecipient('user@fireping.example');

        yield [$email, Mail::class];

        $slack = new SlackDestination();
        $slack->setUrl('https://slack.example');
        $slack->setChannel('channel');

        yield [$slack, Slack::class];
    }
}
