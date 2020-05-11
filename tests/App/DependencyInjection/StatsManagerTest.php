<?php

namespace App\Tests\App\DependencyInjection;

use App\DependencyInjection\StatsManager;
use App\ShellCommand\GetConfigHttpWorkerCommand;
use App\ShellCommand\PostResultsHttpWorkerCommand;
use App\ShellCommand\PostStatsHttpWorkerCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StatsManagerTest extends TestCase
{
    public function testAddFailedPost()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addFailedPost();

        $this->assertEquals(1, $statsManager->getStats()['posts']['failed']);

        //failed posts should reset themself
        $this->assertEquals(0, $statsManager->getStats()['posts']['failed']);
    }

    public function testAddDiscardedPosts()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addDiscardedPost();

        $this->assertEquals(1, $statsManager->getStats()['posts']['discarded']);

        //discarded posts should reset themself
        $this->assertEquals(0, $statsManager->getStats()['posts']['discarded']);
    }

    public function testAddSuccessfulPosts()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addSuccessfulPost();

        $this->assertEquals(1, $statsManager->getStats()['posts']['success']);

        //successful posts should reset themself
        $this->assertEquals(0, $statsManager->getStats()['posts']['success']);
    }

    public function testLoadAverage()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());
        $stats = $statsManager->getStats();

        $this->assertIsArray($stats['load']);
        $this->assertEquals(3, count($stats['load']));
    }

    public function testMemory()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());
        $stats = $statsManager->getStats();

        $this->assertIsArray($stats['memory']);
        $this->assertEquals(6, count($stats['memory']));
    }

    public function testQueueItems()
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

    public function testWorkerStats()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());

        $statsManager->addWorkerStats(10, 5, [
            'ping' => 1,
            'traceroute' => 1,
            'queue' => 1,
            'http' => 1,
            PostStatsHttpWorkerCommand::class => 1,
            GetConfigHttpWorkerCommand::class => 1,
            PostResultsHttpWorkerCommand::class => 1,
            'bla' => 0
        ]);

        $stats = $statsManager->getStats();

        $this->assertIsArray($stats['workers']);
        $key = array_keys($stats['workers'])[0];

        $this->assertEquals(10, $stats['workers'][$key]['total']);
        $this->assertEquals(0, $stats['workers'][$key]['bla']);
        $this->assertEquals(1, $stats['workers'][$key]['stats']);
    }
}