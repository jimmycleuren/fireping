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
    private $slaveGroupId = 0;

    public function setUp() : void
    {
        $this->slaveGroupId = date("U");
    }

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
        $group->setId($this->slaveGroupId);

        $data = [
            "median" => 5,
            "loss" => 0
        ];
        $storage->store($device, $probe, $group, date("U"), $data, true, $_ENV['RRDCACHED_TEST'] ?? "127.0.0.1:42217");

        $datasources = $storage->getDatasources($device, $probe, $group, $_ENV['RRDCACHED_TEST'] ?? "127.0.0.1:42217");

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
        $group->setId($this->slaveGroupId);

        $data = [
            "median" => 5,
            "loss" => 0
        ];
        $storage->store($device, $probe, $group, date("U") + 1, $data, true, $_ENV['RRDCACHED_TEST'] ?? "127.0.0.1:42217");

        $datasources = $storage->getDatasources($device, $probe, $group, $_ENV['RRDCACHED_TEST'] ?? "127.0.0.1:42217");

        $this->assertEquals(["median", "loss"], $datasources);
    }

    public function testUpdateWithNewDatasource()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $storage = new RrdCachedStorage(null, $logger);

        $device = new Device();
        $device->setId(1);

        $probe = new Probe();
        $probe->setId(1);
        $probe->setStep(60);

        $group = new SlaveGroup();
        $group->setId($this->slaveGroupId);

        $data = [
            "median" => 5,
            "loss" => 0,
            "new" => 5,
        ];
        $storage->store($device, $probe, $group, date("U") + 2, $data, true, $_ENV['RRDCACHED_TEST'] ?? "127.0.0.1:42217");

        $datasources = $storage->getDatasources($device, $probe, $group, $_ENV['RRDCACHED_TEST'] ?? "127.0.0.1:42217");

        //TODO: this is correct, but we cannot rrdtune over ssh connections in the test suite
        //$this->assertEquals(["median", "loss", "new"], $datasources);

        $this->assertEquals(["median", "loss"], $datasources);
    }
}