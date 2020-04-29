<?php

declare(strict_types=1);

namespace Tests\App\AlertDestination;

use App\AlertDestination\AlertDestinationFactory;
use App\AlertDestination\Http;
use App\AlertDestination\Mail;
use App\AlertDestination\Monolog;
use App\AlertDestination\Slack;
use App\Entity\AlertDestination;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertDestinationFactoryTest extends WebTestCase
{
    /**
     * @var AlertDestinationFactory
     */
    private $factory;

    /**
     * @dataProvider typeProvider
     */
    public function testCreateHandler(string $type, string $class): void
    {
        $destination = new AlertDestination();
        $destination->setType($type);

        self::assertInstanceOf($class, $this->factory->create($destination));
    }

    public function typeProvider(): array
    {
        return [
            'http handler' => ['http', Http::class],
            'mail handler' => ['mail', Mail::class],
            'monolog handler' => ['monolog', Monolog::class],
            'slack handler' => ['slack', Slack::class],
        ];
    }

    protected function setUp(): void
    {
        $client = static::createClient();
        $this->factory = new AlertDestinationFactory($client->getContainer());
    }
}