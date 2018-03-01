<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 1/03/2018
 * Time: 21:28
 */

namespace Tests\AppBundle\Graph;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Entity\SlaveGroup;
use AppBundle\Exception\RrdException;
use AppBundle\Storage\RrdStorage;
use PHPUnit\Framework\TestCase;
use AppBundle\Graph\PingGraph;
use Prophecy\Argument;

class PingGraphTest extends TestCase
{
    public function testSummaryGraphWithoutRrd()
    {
        $storage = $this->prophesize('AppBundle\\Storage\\RrdStorage');
        $storage->getFilePath(Argument::any(), Argument::any(), Argument::any())->willReturn('/tmp/unknown.rrd')->shouldBeCalledTimes(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(1);
        $device->setName("device1");
        $device->addSlaveGroup($slavegroup);

        $graph = new PingGraph($storage->reveal());
        $image = $graph->getSummaryGraph($device, $probe);
        $this->assertNotNull($image);
    }

    public function testSummaryGraph()
    {
        @unlink('/tmp/2/1/1.rrd');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(2);
        $device->setName("device2");
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = array();
        $data['loss'] = 0;
        $data['median'] = 0;
        for($i = 1; $i < 16; $i++) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date("U"), $data);

        $graph = new PingGraph($storage);
        $image = $graph->getSummaryGraph($device, $probe);
        $this->assertNotNull($image);
    }

    /**
     * @expectedException AppBundle\Exception\RrdException
     */
    public function testSummaryGraphException()
    {
        @unlink('/tmp/3/1/1.rrd');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(3);
        $device->setName("device3");
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = array();
        $data['loss'] = 0;
        $data['median'] = 0;
        for($i = 1; $i < 5; $i++) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date("U"), $data);

        $graph = new PingGraph($storage);
        $graph->getSummaryGraph($device, $probe);
    }

    public function testDetailGraphWithoutRrd()
    {
        $storage = $this->prophesize('AppBundle\\Storage\\RrdStorage');
        $storage->getFilePath(Argument::any(), Argument::any(), Argument::any())->willReturn('/tmp/unknown.rrd')->shouldBeCalledTimes(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(4);
        $device->setName("device4");
        $device->addSlaveGroup($slavegroup);

        $graph = new PingGraph($storage->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, -3600, null, true);
        $this->assertNotNull($image);
    }

    public function testDetailGraph()
    {
        @unlink('/tmp/5/1/1.rrd');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(5);
        $device->setName("device5");
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = array();
        $data['loss'] = 0;
        $data['median'] = 0;
        for($i = 1; $i < 16; $i++) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date("U"), $data);

        $graph = new PingGraph($storage);
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, -3600, null, true);
        $this->assertNotNull($image);
    }

    /**
     * @expectedException AppBundle\Exception\RrdException
     */
    public function testDetailGraphException()
    {
        @unlink('/tmp/6/1/1.rrd');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(6);
        $device->setName("device6");
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = array();
        $data['loss'] = 0;
        $data['median'] = 0;
        for($i = 1; $i < 5; $i++) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date("U"), $data);

        $graph = new PingGraph($storage);
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, -3600, null, true);
        $this->assertNotNull($image);
    }
}