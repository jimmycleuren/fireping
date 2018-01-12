<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 7/01/2018
 * Time: 21:06
 */

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GraphControllerTest extends WebTestCase
{
    public function testDevice1Summary()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/graphs/summary/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1Detail()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/graphs/detail/1/1/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1DetailDummy()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/graphs/detail/1/3/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testDevice2Summary()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/graphs/summary/2');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice3Summary()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/graphs/summary/3');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
