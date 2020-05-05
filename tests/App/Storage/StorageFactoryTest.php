<?php

namespace Tests\App\Storage;

use App\Entity\StorageNode;
use App\Repository\StorageNodeRepository;
use App\Storage\RrdCachedStorage;
use App\Storage\RrdDistributedStorage;
use App\Storage\RrdStorage;
use App\Storage\StorageFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class StorageFactoryTest extends TestCase
{
    public function testCreate()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $storageNodeRepository = $this->prophesize(StorageNodeRepository::class);
        $storageNodeRepository->findBy(Argument::any(), Argument::any())->willReturn([new StorageNode()])->shouldBeCalledTimes(1);
        $storageNodeRepository = $storageNodeRepository->reveal();

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();

        $rrdStorage = new RrdStorage("", $logger);
        $rrdCachedStorage = new RrdCachedStorage("", $logger);
        $rrdDistributedStorage = new RrdDistributedStorage("", $logger, $storageNodeRepository, $entityManager);

        $factory = new StorageFactory($rrdStorage, $rrdCachedStorage, $rrdDistributedStorage);

        $existing = $_ENV['STORAGE'];
        $_ENV['STORAGE'] = 'rrd';
        $this->assertInstanceOf(RrdStorage::class, $factory->create());
        $_ENV['STORAGE'] = 'rrdcached';
        $this->assertInstanceOf(RrdCachedStorage::class, $factory->create());
        $_ENV['STORAGE'] = 'rrddistributed';
        $this->assertInstanceOf(RrdDistributedStorage::class, $factory->create());
        $_ENV['STORAGE'] = $existing;
    }

    public function testException()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $storageNodeRepository = $this->prophesize(StorageNodeRepository::class);
        $storageNodeRepository->findBy(Argument::any(), Argument::any())->willReturn([])->shouldBeCalledTimes(1);
        $storageNodeRepository = $storageNodeRepository->reveal();

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();

        $rrdStorage = new RrdStorage("", $logger);
        $rrdCachedStorage = new RrdCachedStorage("", $logger);
        $rrdDistributedStorage = new RrdDistributedStorage("", $logger, $storageNodeRepository, $entityManager);

        $factory = new StorageFactory($rrdStorage, $rrdCachedStorage, $rrdDistributedStorage);

        $this->expectException(\RuntimeException::class);
        $existing = $_ENV['STORAGE'];
        $_ENV['STORAGE'] = 'bla';
        $factory->create();
        $_ENV['STORAGE'] = $existing;
    }
}