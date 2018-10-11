<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 7/01/2018
 * Time: 21:06
 */

namespace Tests\App\Controller;

use App\Tests\App\Api\AbstractApiTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GraphControllerTest extends AbstractApiTest
{
    public function testDevice1Summary()
    {
        $crawler = $this->client->request('GET', '/api/graphs/summary/1');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1Detail()
    {
        $crawler = $this->client->request('GET', '/api/graphs/detail/1/1/1');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1DetailDummy()
    {
        $crawler = $this->client->request('GET', '/api/graphs/detail/1/3/1');

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
    }

    public function testDevice2Summary()
    {
        $crawler = $this->client->request('GET', '/api/graphs/summary/2');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice3Summary()
    {
        $crawler = $this->client->request('GET', '/api/graphs/summary/3');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }
}
