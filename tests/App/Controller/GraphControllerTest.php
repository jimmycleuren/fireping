<?php

namespace App\Tests\App\Controller;

use App\DependencyInjection\StatsManager;
use App\Storage\SlaveStatsRrdStorage;
use App\Tests\App\Api\AbstractApiTest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GraphControllerTest extends AbstractApiTest
{
    public function testDevice1Summary()
    {
        $this->client->request('GET', '/api/graphs/summary/1');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1Detail()
    {
        $this->client->request('GET', '/api/graphs/detail/1/1/1');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1DetailDummy()
    {
        $this->client->request('GET', '/api/graphs/detail/1/3/1');

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
    }

    public function testDevice2Summary()
    {
        $this->client->request('GET', '/api/graphs/summary/2');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice3Summary()
    {
        $this->client->request('GET', '/api/graphs/summary/3');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    private function addSlaveStats(): void
    {
        $logger = new NullLogger();
        $statsManager = new StatsManager($logger);

        $statsManager->addDiscardedPost();
        $statsManager->addFailedPost();
        $statsManager->addQueueItems(0, 10);
        $statsManager->addSuccessfulPost();
        $statsManager->addWorkerStats(10, 5, ['bla' => 5]);

        $this->client->request('POST', '/api/slaves/slave1/stats', [], [], [], json_encode($statsManager->getStats()));
    }

    public function testSlaveLoad()
    {
        $this->addSlaveStats();

        $this->client->request('GET', '/api/graphs/slaves/slave1/load');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveMemory()
    {
        $this->addSlaveStats();

        $this->client->request('GET', '/api/graphs/slaves/slave1/memory');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlavePosts()
    {
        $this->addSlaveStats();

        $this->client->request('GET', '/api/graphs/slaves/slave1/posts');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveQueues()
    {
        $this->addSlaveStats();

        $this->client->request('GET', '/api/graphs/slaves/slave1/queues');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveWorkers()
    {
        $this->addSlaveStats();

        $this->client->request('GET', '/api/graphs/slaves/slave1/workers');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }
}
