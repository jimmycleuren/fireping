<?php

namespace App\Tests\AlertDestination;

use App\AlertDestination\Gotify;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class GotifyTest extends TestCase
{
    private function buildAlert(): Alert
    {
        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        return $alert;
    }

    public function testNoArguments(): void
    {
        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $gotify = new Gotify($client->reveal(), $logger->reveal());

        $alert = $this->buildAlert();

        $this->assertEquals(false, $gotify->trigger($alert));
        $this->assertEquals(false, $gotify->clear($alert));
    }

    public function testMissingToken(): void
    {
        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $gotify = new Gotify($client->reveal(), $logger->reveal());
        $gotify->setParameters(['url' => 'http://gotify.example.com']);

        $alert = $this->buildAlert();

        $this->assertEquals(false, $gotify->trigger($alert));
        $this->assertEquals(false, $gotify->clear($alert));
    }

    public function testException(): void
    {
        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $client->post('http://gotify.example.com/message', Argument::any())->shouldBeCalledTimes(2)->willThrow(new \Exception('test'));
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);
        $logger->error(Argument::type('string'))->shouldBeCalledTimes(2);

        $gotify = new Gotify($client->reveal(), $logger->reveal());
        $gotify->setParameters(['url' => 'http://gotify.example.com', 'token' => 'abc']);

        $alert = $this->buildAlert();

        $gotify->trigger($alert);
        $gotify->clear($alert);
    }

    public function testTrigger(): void
    {
        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $client->post('http://gotify.example.com/message', Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $gotify = new Gotify($client->reveal(), $logger->reveal());
        $gotify->setParameters(['url' => 'http://gotify.example.com/', 'token' => 'abc']);

        $gotify->trigger($this->buildAlert());
    }

    public function testClear(): void
    {
        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $client->post('http://gotify.example.com/message', Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $gotify = new Gotify($client->reveal(), $logger->reveal());
        $gotify->setParameters(['url' => 'http://gotify.example.com', 'token' => 'abc']);

        $gotify->clear($this->buildAlert());
    }
}
