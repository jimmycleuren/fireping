<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:39
 */

namespace Tests\App\AlertDestination;

use App\AlertDestination\Monolog;
use App\Entity\Alert;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class MonologTest extends TestCase
{
    public function testTrigger()
    {
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->warning(Argument::is('FIREPING.ALERT: rule on device from group'))->shouldBeCalledTimes(1);
        $monolog = new Monolog($logger->reveal());

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

        $monolog->trigger($alert);
    }

    public function testClear()
    {
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $logger->warning(Argument::is('FIREPING.CLEAR: rule on device from group'))->shouldBeCalledTimes(1);
        $monolog = new Monolog($logger->reveal());

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

        $monolog->clear($alert);
    }
}