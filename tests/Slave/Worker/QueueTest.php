<?php

namespace App\Tests\Slave\Worker;

use App\Slave\Worker\Queue;
use App\Slave\Worker\StatsManager;
use App\Slave\Worker\Worker;
use App\Slave\Worker\WorkerManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class QueueTest extends TestCase
{
    public function testQueueSameTimestamp(): void
    {
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $worker = $this->prophesize(Worker::class);
        $worker->__toString()->willReturn('name');
        $worker->send(Argument::type('string'), Argument::type('integer'), Argument::any())->willReturn();
        //$worker->getPid()->willReturn(1234)->shouldBeCalledTimes(1);

        $workerManager = $this->prophesize(WorkerManager::class);
        $workerManager->getWorker(Argument::any())->willReturn($worker->reveal())->shouldBeCalledTimes(1);
        //$workerManager->sendInstruction(Argument::any(), 1234)->shouldBeCalledTimes(1);

        $statsManager = $this->prophesize(StatsManager::class);

        $queue = new Queue($workerManager->reveal(), $statsManager->reveal(), 1, $logger->reveal());

        $queue->enqueue($this->getData(1, 1000, 10));
        $queue->enqueue($this->getData(1, 1000, 11));
        $queue->enqueue($this->getData(1, 1000, 12));
        $queue->enqueue($this->getData(1, 1000, 13));
        $queue->enqueue($this->getData(1, 1000, 14));
        $queue->enqueue($this->getData(1, 1000, 15));
        $queue->enqueue($this->getData(1, 1000, 16));
        $queue->enqueue($this->getData(1, 1000, 17));
        $queue->enqueue($this->getData(1, 1000, 18));
        $queue->enqueue($this->getData(1, 1000, 19));
        $queue->enqueue($this->getData(1, 1000, 20));
        $queue->enqueue($this->getData(1, 1000, 21));
        $queue->enqueue($this->getData(1, 1000, 22));
        $queue->enqueue($this->getData(1, 1000, 23));

        $queue->loop();
        $queue->loop();
        $queue->loop();
        $queue->loop();
    }

    public function testQueue3Timestamps(): void
    {
        $logger = $this->prophesize('Psr\\Log\\LoggerInterface');

        $worker = $this->prophesize(Worker::class);
        $worker->__toString()->willReturn('name');
        $worker->send(Argument::type('string'), Argument::type('integer'), Argument::any())->willReturn();
        //$worker->getPid()->willReturn(1234)->shouldBeCalledTimes(3);

        $workerManager = $this->prophesize(WorkerManager::class);
        $workerManager->getWorker(Argument::any())->willReturn($worker->reveal())->shouldBeCalledTimes(1);
        //$dispatcher->sendInstruction(Argument::any(), 1234)->shouldBeCalledTimes(3);

        $statsManager = $this->prophesize(StatsManager::class);

        $queue = new Queue($workerManager->reveal(), $statsManager->reveal(), 1, $logger->reveal());

        $queue->enqueue($this->getData(1, 1000, 10));
        $queue->enqueue($this->getData(1, 1000, 11));
        $queue->enqueue($this->getData(1, 1000, 12));
        $queue->enqueue($this->getData(1, 1000, 13));
        $queue->enqueue($this->getData(1, 1000, 14));
        $queue->enqueue($this->getData(1, 1000, 15));
        $queue->enqueue($this->getData(1, 2000, 16));
        $queue->enqueue($this->getData(1, 2000, 17));
        $queue->enqueue($this->getData(1, 2000, 18));
        $queue->enqueue($this->getData(1, 2000, 19));
        $queue->enqueue($this->getData(1, 2000, 20));
        $queue->enqueue($this->getData(1, 2000, 21));
        $queue->enqueue($this->getData(1, 3000, 22));
        $queue->enqueue($this->getData(1, 3000, 23));

        $queue->loop();
        $queue->loop();
        $queue->loop();
        $queue->loop();
        $queue->loop();
        $queue->loop();
    }

    private function getData($probeId, $timestamp, $targetId)
    {
        return [
            $probeId => [
                'type' => 'ping',
                'timestamp' => $timestamp,
                'targets' => [
                    $targetId => [1, 2, 3, 4, 5, 6],
                ],
            ],
        ];
    }
}
