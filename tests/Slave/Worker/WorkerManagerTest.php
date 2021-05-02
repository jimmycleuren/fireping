<?php

namespace App\Tests\Slave\Worker;

use App\Kernel;
use App\Slave\Configuration;
use App\Slave\Worker\WorkerManager;
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

        $worker1->release();
        $worker2->release();
        $worker3->release();
        $worker4->release();

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
        $worker->send("", -1, function () {
        });

        $this->assertEquals(3, $manager->getTotalWorkers());
        $this->assertEquals(2, $manager->getAvailableWorkers());
        $this->assertEquals(['bla' => 1], $manager->getInUseWorkerTypes());

        $manager->setNumberOfProbeProcesses(0); //lower the baseline so no new worker is created
        $manager->loop(); //triggers a worker timeout

        $this->assertEquals(2, $manager->getTotalWorkers());
        $this->assertEquals(2, $manager->getAvailableWorkers());
        $this->assertEquals(['bla' => 0], $manager->getInUseWorkerTypes());
    }

    /**
     * @dataProvider configurationProvider
     * @param Configuration $configuration
     * @param int $expected
     */
    public function testCalculateNumberOfProbeProcesses(Configuration $configuration, int $expected)
    {
        $manager = new WorkerManager(
            $this->prophesize(Kernel::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $manager->setNumberOfProbeProcesses($configuration);

        self::assertEquals($manager->getNumberOfProbeProcesses(), $expected);
    }

    public function configurationProvider(): array
    {
        return [
            [
                new Configuration('*', \array_merge(
                    $this->createProbeConfiguration(1, 'ping', 60, 15, 60),
                    $this->createProbeConfiguration(2, 'ping', 60, 15, 501),
                    $this->createProbeConfiguration(3, 'foobar', 60, 15, 60),
                )), 5
            ],
            [
                new Configuration('*', \array_merge(
                    $this->createProbeConfiguration(1, 'ping', 60, 15, 60),
                    $this->createProbeConfiguration(2, 'ping', 60, 15, 60),
                    $this->createProbeConfiguration(3, 'foobar', 60, 15, 60),
                )), 3
            ]
        ];
    }

    private function createProbeConfiguration(int $id, string $type, int $step, int $samples, int $deviceCount): array
    {
        $targets = [];
        for ($i = 0; $i < $deviceCount; $i++) {
            $targets[$i] = "192.168.1.$i";
        }

        return [
            $id => [
                'type' => $type,
                'samples' => $samples,
                'step' => $step,
                'targets' => $targets
            ]
        ];
    }
}
