<?php

namespace App\Tests\AlertDestination;

use App\AlertDestination\Slack;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class SlackTest extends TestCase
{
    public function testNoArguments(): void
    {
        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $slack = new Slack($client->reveal(), $logger->reveal());

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

        $this->assertEquals(false, $slack->trigger($alert));
        $this->assertEquals(false, $slack->clear($alert));
    }

    public function testException(): void
    {
        $url = 'http://slack.com';

        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $client->post($url, Argument::any())->shouldBeCalledTimes(2)->willThrow(new \Exception('test'));
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);
        $logger->error(Argument::type('string'))->shouldBeCalledTimes(2);

        $slack = new Slack($client->reveal(), $logger->reveal());
        $slack->setParameters(['url' => $url, 'channel' => 'general']);

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

        $slack->trigger($alert);
        $slack->clear($alert);
    }

    public function testTrigger(): void
    {
        $url = 'http://slack.com';

        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $client->post($url, Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $slack = new Slack($client->reveal(), $logger->reveal());
        $slack->setParameters(['url' => $url, 'channel' => 'general']);

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

        $slack->trigger($alert);
    }

    public function testClear(): void
    {
        $url = 'http://slack.com';

        $client = $this->prophesize(\GuzzleHttp\Client::class);
        $client->post($url, Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $slack = new Slack($client->reveal(), $logger->reveal());
        $slack->setParameters(['url' => $url, 'channel' => 'general']);

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

        $slack->clear($alert);
    }
}
