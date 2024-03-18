<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SlaveControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/slaves');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Slaves', $crawler->filter('h1')->text());
    }

    public function testDetail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/slaves/slave1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Slave slave1', $crawler->filter('h1')->text());
    }
}
