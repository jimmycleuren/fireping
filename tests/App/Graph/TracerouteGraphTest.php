<?php

namespace App\Tests\App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\ProbeArchive;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;
use App\Graph\PingGraph;
use App\Graph\TracerouteGraph;
use App\Storage\RrdStorage;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class TracerouteGraphTest extends TestCase
{
    use ProphecyTrait;

    public function testDetailGraphWithoutRrd()
    {
        $storage = $this->prophesize('App\\Storage\\RrdStorage');
        $storage->fileExists(Argument::type(Device::class), '/tmp/unknown.rrd')->shouldBeCalledTimes(1);
        $storage->getFilePath(Argument::any(), Argument::any(), Argument::any())->willReturn('/tmp/unknown.rrd')->shouldBeCalledTimes(1);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage->reveal())->shouldBeCalledTimes(1);

        $helper = $this->prophesize('App\\DependencyInjection\\Helper');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('traceroute');
        $probe->setType('traceroute');
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(7);
        $device->setName('device7');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $graph = new TracerouteGraph($storageFactory->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, $helper->reveal(), -3600, null, true);
        $this->assertNotNull($image);
    }

    public function testDetailGraph()
    {
        @unlink('/tmp/8/1/1.rrd');

        $archive1 = new ProbeArchive();
        $archive1->setFunction('AVERAGE');
        $archive1->setSteps(1);
        $archive1->setRows(1008);

        $archive2 = new ProbeArchive();
        $archive2->setFunction('MAX');
        $archive2->setSteps(1);
        $archive2->setRows(1008);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('traceroute');
        $probe->setType('traceroute');
        $probe->setSamples(15);
        $probe->setStep(60);
        $probe->addArchive($archive1);
        $probe->addArchive($archive2);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(8);
        $device->setName('device8');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data['1_1_1_1_1m'] = 1;
        $data['1_1_1_1_1l'] = 0;
        $data['2_8_8_8_8m'] = 1;
        $data['2_8_8_8_8l'] = 0;

        $storage->store($device, $probe, $slavegroup, date('U') - 60, $data, true);
        $storage->store($device, $probe, $slavegroup, date('U'), $data, true);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage)->shouldBeCalledTimes(1);

        $helper = $this->prophesize('App\\DependencyInjection\\Helper');

        $graph = new TracerouteGraph($storageFactory->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, $helper->reveal(), -3600, null, true);
        $this->assertNotNull($image);
    }

    public function testDetailGraphException()
    {
        @unlink('/tmp/9/1/1.rrd');

        $probe = new Probe();
        $probe->setId(1);
        $probe->setName('traceroute');
        $probe->setType('traceroute');
        $probe->setSamples(15);
        $probe->setStep(60);

        $slavegroup = new SlaveGroup();
        $slavegroup->setId(1);

        $device = new Device();
        $device->setId(9);
        $device->setName('device9');
        $device->setIp('8.8.8.8');
        $device->addSlaveGroup($slavegroup);

        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');
        $storage = new RrdStorage('/tmp/', $logger->reveal());

        $data['1_1_1_1_1m'] = 1;
        $data['1_1_1_1_1l'] = 0;
        $data['2_8_8_8_8m'] = 1;
        $data['2_8_8_8_8l'] = 0;

        $storage->store($device, $probe, $slavegroup, date('U'), $data, true);

        $storageFactory = $this->prophesize('App\\Storage\\StorageFactory');
        $storageFactory->create()->willReturn($storage)->shouldBeCalledTimes(1);

        $helper = $this->prophesize('App\\DependencyInjection\\Helper');

        $this->expectException(RrdException::class);
        $graph = new TracerouteGraph($storageFactory->reveal());
        $image = $graph->getDetailGraph($device, $probe, $slavegroup, $helper->reveal(), -3600, null, true);
        $this->assertNotNull($image);
    }
}
