<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 20:39
 */

namespace Tests\AppBundle\AlertDestination;

use AppBundle\AlertDestination\Http;
use AppBundle\AlertDestination\Monolog;
use AppBundle\Entity\Alert;
use AppBundle\Entity\AlertRule;
use AppBundle\Entity\Device;
use AppBundle\Entity\SlaveGroup;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class HttpTest extends TestCase
{
    public function testTrigger()
    {
        $guzzle = $this->prophesize('GuzzleHttp\\Client');
        $guzzle->post("url", Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $http = new Http($guzzle->reveal(), $logger->reveal());
        $http->setParameters(json_encode(array('url' => 'url')));

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

        $http->trigger($alert);
    }

    public function testClear()
    {
        $guzzle = $this->prophesize('GuzzleHttp\\Client');
        $guzzle->post("url", Argument::any())->shouldBeCalledTimes(1);
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $http = new Http($guzzle->reveal(), $logger->reveal());
        $http->setParameters(json_encode(array('url' => 'url')));

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

        $http->clear($alert);
    }
}