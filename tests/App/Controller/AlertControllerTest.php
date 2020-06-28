<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:04.
 */

namespace Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/alerts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Active alerts', $crawler->filter('h1')->text());
    }

    public function testDomain()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/alerts/domain/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Active alerts', $crawler->filter('h1')->text());
    }
}
