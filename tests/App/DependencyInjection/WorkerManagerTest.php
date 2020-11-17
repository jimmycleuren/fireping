<?php

namespace App\Tests\App\DependencyInjection;

use App\DependencyInjection\WorkerManager;
use App\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkerManagerTest extends TestCase
{
    public function testFlow()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $kernel = $this->prophesize(Kernel::class);

        $manager = new WorkerManager($kernel->reveal(), $logger->reveal());

        $manager->setNumberOfProbeProcesses(1);
        $manager->initialize(1, 5, 1);

        $this->assertEquals(4, $manager->getTotalWorkers());
        $this->assertEquals(4, $manager->getAvailableWorkers());
        $this->assertEquals([], $manager->getInUseWorkerTypes());

        $worker1 = $manager->getWorker('bla');

        $this->assertEquals(4, $manager->getTotalWorkers());
        $this->assertEquals(3, $manager->getAvailableWorkers());
        $this->assertEquals(['bla' => 1], $manager->getInUseWorkerTypes());

        $worker2 = $manager->getWorker('bla');
        $worker3 = $manager->getWorker('bla');
        $worker4 = $manager->getWorker('bla');

        $this->assertEquals(4, $manager->getTotalWorkers());
        $this->assertEquals(0, $manager->getAvailableWorkers());
        $this->assertEquals(['bla' => 4], $manager->getInUseWorkerTypes());

        $manager->release($worker1);
        $manager->release($worker2);
        $manager->release($worker3);
        $manager->release($worker4);

        $this->assertEquals(4, $manager->getTotalWorkers());
        $this->assertEquals(4, $manager->getAvailableWorkers());
        $this->assertEquals(['bla' => 0], $manager->getInUseWorkerTypes());
    }

    public function testNoWorker()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $kernel = $this->prophesize(Kernel::class);

        $manager = new WorkerManager($kernel->reveal(), $logger->reveal());

        $manager->setNumberOfProbeProcesses(0);
        $manager->initialize(1, 5, 0);

        $this->expectException(\RuntimeException::class);

        $manager->getWorker('bla');
        $manager->getWorker('bla');
    }

    public function testNotEnoughWorkers()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $kernel = $this->prophesize(Kernel::class);

        $manager = new WorkerManager($kernel->reveal(), $logger->reveal());

        $manager->setNumberOfProbeProcesses(0);
        $manager->initialize(1, 2, 0);

        $this->assertEquals(1, $manager->getTotalWorkers());
        $this->assertEquals(1, $manager->getAvailableWorkers());

        $manager->loop();

        $this->assertEquals(1, $manager->getTotalWorkers());
        $this->assertEquals(1, $manager->getAvailableWorkers());

        $manager->setNumberOfProbeProcesses(1);

        $this->assertEquals(1, $manager->getTotalWorkers());
        $this->assertEquals(1, $manager->getAvailableWorkers());

        $manager->loop();

        $this->assertEquals(2, $manager->getTotalWorkers());
        $this->assertEquals(2, $manager->getAvailableWorkers());

        $manager->loop(); //we've reached the maximum of 2 workers, this will not create an extra one

        $this->assertEquals(2, $manager->getTotalWorkers());
        $this->assertEquals(2, $manager->getAvailableWorkers());
    }

    public function testTimeout()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $kernel = $this->prophesize(Kernel::class);

        $manager = new WorkerManager($kernel->reveal(), $logger->reveal());

        $manager->setNumberOfProbeProcesses(1);
        $manager->initialize(1, 2, 0);

        $worker = $manager->getWorker('bla');
        $worker->send("", -1, function(){});

        $this->assertEquals(3, $manager->getTotalWorkers());
        $this->assertEquals(2, $manager->getAvailableWorkers());
        $this->assertEquals(['bla' => 1], $manager->getInUseWorkerTypes());

        $manager->setNumberOfProbeProcesses(0); //lower the baseline so no new worker is created
        $manager->loop(); //triggers a worker timeout

        $this->assertEquals(2, $manager->getTotalWorkers());
        $this->assertEquals(2, $manager->getAvailableWorkers());
        $this->assertEquals(['bla' => 0], $manager->getInUseWorkerTypes());
    }
}
