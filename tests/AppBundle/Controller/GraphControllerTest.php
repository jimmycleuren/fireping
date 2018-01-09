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
    public function testDomain()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/graphs/summary/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }
}
