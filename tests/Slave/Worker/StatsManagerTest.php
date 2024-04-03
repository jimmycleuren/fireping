<?php

namespace App\Tests\Slave\Worker;

use App\Slave\Task\FetchConfiguration;
use App\Slave\Task\PublishResults;
use App\Slave\Task\PublishStatistics;
use App\Slave\Worker\StatsManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StatsManagerTest extends TestCase
{
    public function testAddFailedPost(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addFailedPost();

        $this->assertEquals(1, $statsManager->getStats()['posts']['failed']);

        //failed posts should reset themself
        $this->assertEquals(0, $statsManager->getStats()['posts']['failed']);
    }

    public function testAddDiscardedPosts(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addDiscardedPost();

        $this->assertEquals(1, $statsManager->getStats()['posts']['discarded']);

        //discarded posts should reset themself
        $this->assertEquals(0, $statsManager->getStats()['posts']['discarded']);
    }

    public function testAddSuccessfulPosts(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addSuccessfulPost();

        $this->assertEquals(1, $statsManager->getStats()['posts']['success']);

        //successful posts should reset themself
        $this->assertEquals(0, $statsManager->getStats()['posts']['success']);
    }

    public function testLoadAverage(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());
        $stats = $statsManager->getStats();

        $this->assertIsArray($stats['load']);
        $this->assertEquals(3, count($stats['load']));
    }

    public function testMemory(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());
        $stats = $statsManager->getStats();

        $this->assertIsArray($stats['memory']);
        $this->assertEquals(6, count($stats['memory']));
    }

    public function testQueueItems(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addQueueItems(0, 5);
        $statsManager->addQueueItems(1, 10);
        sleep(1);
        $statsManager->addQueueItems(0, 50);
        $statsManager->addQueueItems(1, 100);

        $stats = $statsManager->getStats();
        $this->assertIsArray($stats['queues']);
        $this->assertEquals(2, count($stats['queues']));
        $this->assertEquals(5, array_values($stats['queues'])[0][0]);
        $this->assertEquals(10, array_values($stats['queues'])[0][1]);
        $this->assertEquals(50, array_values($stats['queues'])[1][0]);
        $this->assertEquals(100, array_values($stats['queues'])[1][1]);
    }

    public function testWorkerStats(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addWorkerStats(10, 5, [
            'ping' => 1,
            'traceroute' => 1,
            'queue' => 1,
            'http' => 1,
            PublishStatistics::class => 1,
            FetchConfiguration::class => 1,
            PublishResults::class => 1,
            'bla' => 0,
        ]);

        $stats = $statsManager->getStats();

        $this->assertIsArray($stats['workers']);
        $key = array_keys($stats['workers'])[0];

        $this->assertEquals(10, $stats['workers'][$key]['total']);
        $this->assertEquals(0, $stats['workers'][$key]['bla']);
        $this->assertEquals(1, $stats['workers'][$key]['stats']);
    }
}
