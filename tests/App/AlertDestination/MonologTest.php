<?php

namespace Tests\App\AlertDestination;

use App\AlertDestination\Monolog;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class MonologTest extends TestCase
{
    use ProphecyTrait;

    public function testTrigger()
    {
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->warning(Argument::is('FIREPING.ALERT: Device down: device from group'))->shouldBeCalledTimes(1);
        $monolog = new Monolog($logger->reveal());

        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alertRule->setMessageUp("Device up");
        $alertRule->setMessageDown("Device down");
        $alert = new Alert();
        $alert->setActive(1);
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        $monolog->trigger($alert);
    }

    public function testClear()
    {
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->warning(Argument::is('FIREPING.CLEAR: Device up: device from group'))->shouldBeCalledTimes(1);
        $monolog = new Monolog($logger->reveal());

        $device = new Device();
        $device->setName('device');
        $slaveGroup = new SlaveGroup();
        $slaveGroup->setName('group');
        $alertRule = new AlertRule();
        $alertRule->setName('rule');
        $alertRule->setMessageUp("Device up");
        $alertRule->setMessageDown("Device down");
        $alert = new Alert();
        $alert->setDevice($device);
        $alert->setSlaveGroup($slaveGroup);
        $alert->setAlertRule($alertRule);

        $monolog->clear($alert);
    }
}