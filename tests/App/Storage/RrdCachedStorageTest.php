<?php

namespace Tests\App\Storage;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Storage\RrdCachedStorage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RrdCachedStorageTest extends TestCase
{
    public function testCreate()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $storage = new RrdCachedStorage(null, $logger);

        $device = new Device();
        $device->setId(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setStep(60);

        $group = new SlaveGroup();
        $group->setId(1);

        $data = [
            "median" => 5,
            "loss" => 0
        ];
        $storage->store($device, $probe, $group, date("U"), $data, true, $_ENV['RRDCACHED_TEST']);

        $datasources = $storage->getDatasources($device, $probe, $group, $_ENV['RRDCACHED_TEST']);

        $this->assertEquals(["median", "loss"], $datasources);
    }

    public function testUpdate()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $storage = new RrdCachedStorage(null, $logger);

        $device = new Device();
        $device->setId(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setStep(60);

        $group = new SlaveGroup();
        $group->setId(1);

        $data = [
            "median" => 5,
            "loss" => 0
        ];
        $storage->store($device, $probe, $group, date("U"), $data, true, $_ENV['RRDCACHED_TEST']);

        $datasources = $storage->getDatasources($device, $probe, $group, $_ENV['RRDCACHED_TEST']);

        $this->assertEquals(["median", "loss"], $datasources);
    }
}