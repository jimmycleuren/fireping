<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 21:11.
 */

namespace Tests\App\AlertDestination;

use App\AlertDestination\AlertDestinationFactory;
use App\Entity\AlertDestination;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertDestinationFactoryTest extends WebTestCase
{
    public function testCreateHttp()
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new AlertDestination();
        $alertDestination->setType('http');
        $http = $factory->create($alertDestination);

        $this->assertEquals('App\AlertDestination\Http', get_class($http));
    }

    public function testCreateMonolog()
    {
        $client = static::createClient();

        $factory = new AlertDestinationFactory($client->getContainer());

        $alertDestination = new AlertDestination();
        $alertDestination->setType('monolog');
        $monolog = $factory->create($alertDestination);

        $this->assertEquals('App\AlertDestination\Monolog', get_class($monolog));
    }
}
