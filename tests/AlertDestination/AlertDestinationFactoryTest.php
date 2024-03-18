<?php

namespace App\Tests\AlertDestination;

use App\AlertDestination\AlertDestinationFactory;
use App\Entity\AlertDestination;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertDestinationFactoryTest extends WebTestCase
{
    public function testCreateHttp(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new AlertDestination();
        $alertDestination->setType('http');
        $http = $factory->create($alertDestination);

        $this->assertEquals('App\AlertDestination\Http', get_class($http));
    }

    public function testCreateMonolog(): void
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new AlertDestination();
        $alertDestination->setType('monolog');
        $monolog = $factory->create($alertDestination);

        $this->assertEquals('App\AlertDestination\Monolog', get_class($monolog));
    }
}
