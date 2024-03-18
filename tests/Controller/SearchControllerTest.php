<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function testRedirect(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/search');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testDomainSearch(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/search?q=domain');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Domain 1', $crawler->filter('.box-primary li')->text());
    }

    public function testDeviceSearch(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/search?q=device');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Device 1', $crawler->filter('h3')->text());
    }

    public function testSearchIsNotInjectable(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/search?q=device\'');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
