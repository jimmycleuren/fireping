<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeviceControllerTest extends WebTestCase
{
    public function testDevice()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/device/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Device 1 - 8.8.8.8', $crawler->filter('h1')->text());
    }
}
