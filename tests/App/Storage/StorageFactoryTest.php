<?php

namespace Tests\App\Storage;

use App\Entity\StorageNode;
use App\Repository\StorageNodeRepository;
use App\Storage\RrdCachedStorage;
use App\Storage\RrdDistributedStorage;
use App\Storage\RrdStorage;
use App\Storage\StorageFactory;
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

        $rrdStorage = new RrdStorage("", $logger);
        $rrdCachedStorage = new RrdCachedStorage("", $logger);
        $rrdDistributedStorage = new RrdDistributedStorage($logger, $storageNodeRepository);

        $factory = new StorageFactory($rrdStorage, $rrdCachedStorage, $rrdDistributedStorage);

        $existing = getenv('STORAGE');
        putenv("STORAGE=rrd");
        $this->assertInstanceOf(RrdStorage::class, $factory->create());
        putenv("STORAGE=rrdcached");
        $this->assertInstanceOf(RrdCachedStorage::class, $factory->create());
        putenv("STORAGE=rrddistributed");
        $this->assertInstanceOf(RrdDistributedStorage::class, $factory->create());
        putenv("STORAGE=".$existing);
    }

    /**
     * @@expectedException \RuntimeException
     */
    public function testException()
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $storageNodeRepository = $this->prophesize(StorageNodeRepository::class);
        $storageNodeRepository->findBy(Argument::any(), Argument::any())->willReturn([])->shouldBeCalledTimes(1);
        $storageNodeRepository = $storageNodeRepository->reveal();

        $rrdStorage = new RrdStorage("", $logger);
        $rrdCachedStorage = new RrdCachedStorage("", $logger);
        $rrdDistributedStorage = new RrdDistributedStorage($logger, $storageNodeRepository);

        $factory = new StorageFactory($rrdStorage, $rrdCachedStorage, $rrdDistributedStorage);

        $existing = getenv('STORAGE');
        putenv("STORAGE=bla");
        $factory->create();
        putenv("STORAGE=".$existing);
    }
}