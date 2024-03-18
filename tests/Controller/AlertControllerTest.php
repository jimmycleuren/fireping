<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/alerts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Active alerts', $crawler->filter('h1')->text());
    }

    public function testDomain(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/alerts/domain/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Active alerts', $crawler->filter('h1')->text());
    }
}
