<?php

namespace App\Tests\Controller\API;

class GraphControllerTestCase extends BaseControllerTestCase
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

    public function testSlaveLoad()
    {
        $this->client->request('GET', '/api/graphs/slaves/slave1/load');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveMemory()
    {
        $this->client->request('GET', '/api/graphs/slaves/slave1/memory');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlavePosts()
    {
        $this->client->request('GET', '/api/graphs/slaves/slave1/posts');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveQueues()
    {
        $this->client->request('GET', '/api/graphs/slaves/slave1/queues');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveWorkers()
    {
        $this->client->request('GET', '/api/graphs/slaves/slave1/workers');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }
}
