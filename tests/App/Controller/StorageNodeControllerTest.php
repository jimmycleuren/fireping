<?php

namespace Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StorageControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/storagenode');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Storage nodes', $crawler->filter('h1')->text());
    }
}
