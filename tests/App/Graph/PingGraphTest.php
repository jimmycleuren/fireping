<?php

namespace App\Tests\App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\ProbeArchive;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;
use App\Graph\PingGraph;
use App\Storage\RrdStorage;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class PingGraphTest extends TestCase
{
    public function testSummaryGraphWithoutRrd()
    {
        $storage = $this->prophesize('App\\Storage\\RrdStorage');
        $storage->fileExists(Argument::type(Device::class), '/tmp/unknown.rrd')->shouldBeCalledTimes(1);
        $storage->getFilePath(Argument::any(), Argument::any(), Argument::any())->willReturn('/tmp/unknown.rrd')->shouldBeCalledTimes(1);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage->reveal())->shouldBeCalledTimes(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('ping');
        $probe->setType('ping');
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(1);
        $device->setName('device1');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $graph = new PingGraph($storageFactory->reveal());
        $image = $graph->getSummaryGraph($device, $probe);
        $this->assertNotNull($image);
    }

    public function testSummaryGraph()
    {
        @unlink('/tmp/2/1/1.rrd');

        $archive = new ProbeArchive();
        $archive->setFunction('AVERAGE');
        $archive->setSteps(1);
        $archive->setRows(1008);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('ping');
        $probe->setType('ping');
        $probe->setSamples(15);
        $probe->setStep(60);
        $probe->addArchive($archive);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(2);
        $device->setName('device2');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = [];
        $data['loss'] = 0;
        $data['median'] = 0;
        for ($i = 1; $i < 16; ++$i) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date('U'), $data);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage)->shouldBeCalledTimes(1);

        $graph = new PingGraph($storageFactory->reveal());
        $image = $graph->getSummaryGraph($device, $probe);
        $this->assertNotNull($image);
    }

    public function testSummaryGraphException()
    {
        @unlink('/tmp/3/1/1.rrd');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('ping');
        $probe->setType('ping');
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(3);
        $device->setName('device3');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = [];
        $data['loss'] = 0;
        $data['median'] = 0;
        for ($i = 1; $i < 5; ++$i) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date('U'), $data);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage)->shouldBeCalledTimes(1);

        $this->expectException(RrdException::class);
        $graph = new PingGraph($storageFactory->reveal());
        $graph->getSummaryGraph($device, $probe);
    }

    public function testDetailGraphWithoutRrd()
    {
        $storage = $this->prophesize('App\\Storage\\RrdStorage');
        $storage->fileExists(Argument::type(Device::class), '/tmp/unknown.rrd')->shouldBeCalledTimes(1);
        $storage->getFilePath(Argument::any(), Argument::any(), Argument::any())->willReturn('/tmp/unknown.rrd')->shouldBeCalledTimes(1);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage->reveal())->shouldBeCalledTimes(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('ping');
        $probe->setType('ping');
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(4);
        $device->setName('device4');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $graph = new PingGraph($storageFactory->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, -3600, null, true);
        $this->assertNotNull($image);
    }

    public function testDetailGraph()
    {
        @unlink('/tmp/5/1/1.rrd');

        $archive = new ProbeArchive();
        $archive->setFunction('AVERAGE');
        $archive->setSteps(1);
        $archive->setRows(1008);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('ping');
        $probe->setType('ping');
        $probe->setSamples(15);
        $probe->setStep(60);
        $probe->addArchive($archive);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(5);
        $device->setName('device5');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = [];
        $data['loss'] = 0;
        $data['median'] = 0;
        for ($i = 1; $i < 16; ++$i) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date('U'), $data);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage)->shouldBeCalledTimes(1);

        $graph = new PingGraph($storageFactory->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, -3600, null, true);
        $this->assertNotNull($image);
    }

    public function testDetailGraphException()
    {
        @unlink('/tmp/6/1/1.rrd');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('ping');
        $probe->setType('ping');
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(6);
        $device->setName('device6');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data = [];
        $data['loss'] = 0;
        $data['median'] = 0;
        for ($i = 1; $i < 5; ++$i) {
            $data["ping$i"] = 0;
        }

        $storage->store($device, $probe, $slavegroup, date('U'), $data);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage)->shouldBeCalledTimes(1);

        $this->expectException(RrdException::class);
        $graph = new PingGraph($storageFactory->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, -3600, null, true);
        $this->assertNotNull($image);
    }
}
