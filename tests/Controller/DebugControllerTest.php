<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DebugControllerTest extends WebTestCase
{
    public function testDebug(): void
    {
        $client = static::createClient();
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $checkbox = $crawler->filter('.control-sidebar-subheading input');
        $this->assertEquals(null, $checkbox->attr('checked'));

        $crawler = $client->request('GET', '/debug');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $checkbox = $crawler->filter('.control-sidebar-subheading input');
        $this->assertEquals('checked', $checkbox->attr('checked'));
    }
}
