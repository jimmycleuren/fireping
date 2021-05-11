<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DomainControllerTest extends WebTestCase
{
    public function testDomain()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/domain/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Domain 1', $crawler->filter('h1')->text());
    }
}
