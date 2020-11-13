<?php

namespace App\Tests\App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\ProbeArchive;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;
use App\Graph\HttpGraph;
use App\Storage\RrdStorage;
use PHPUnit\Framework\TestCase;
use App\Graph\PingGraph;
use Prophecy\Argument;

class HttpGraphTest extends TestCase
{
    public function testResponseGraph()
    {
        @unlink('/tmp/2/1/1.rrd');

        $archive = new ProbeArchive();
        $archive->setFunction('AVERAGE');
        $archive->setSteps(1);
        $archive->setRows(1008);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('http');
        $probe->setType('http');
        $probe->setSamples(15);
        $probe->setStep(60);
        $probe->addArchive($archive);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(2);
        $device->setName("device2");
        $device->setIp('www.google.be');
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = array();
        $data['loss'] = 0;
        $data['median'] = 10;
        for($i = 1; $i < 16; $i++) {
            $data["latency$i"] = 10;
            $data["code$i"] = 200;
        }

        $storage->store($device, $probe, $slavegroup, date("U") - 60, $data);
        $storage->store($device, $probe, $slavegroup, date("U"), $data);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage)->shouldBeCalledTimes(1);

        $graph = new HttpGraph($storageFactory->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, -3600, null, "response");
        $this->assertNotNull($image);
    }

    public function testGradient()
    {
        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn(null)->shouldBeCalledTimes(1);

        $graph = new HttpGraph($storageFactory->reveal());

        $codes = [200];

        $this->assertEquals("006900", $graph->getColor(200, $codes));

        $codes = [200, 201, 202, 203];

        $this->assertEquals("006900", $graph->getColor(200, $codes));
        $this->assertEquals("009b00", $graph->getColor(201, $codes));
        $this->assertEquals("00ff00", $graph->getColor(203, $codes));

        $codes = [400, 401, 402, 403, 404, 405, 406, 407, 499];

        $this->assertEquals("690000", $graph->getColor(400, $codes));
        $this->assertEquals("7b0000", $graph->getColor(401, $codes));
        $this->assertEquals("f90000", $graph->getColor(499, $codes));

        $codes = [500, 501];

        $this->assertEquals("690069", $graph->getColor(500, $codes));
        $this->assertEquals("ff00ff", $graph->getColor(501, $codes));
    }
}