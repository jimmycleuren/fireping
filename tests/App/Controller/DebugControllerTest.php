<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 20:54
 */

namespace Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DebugControllerTest extends WebTestCase
{
    public function testDebug()
    {
        $client = static::createClient();
        $client->followRedirects(true);

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<a class="label label-danger" href="/debug">Debug mode</a>', $client->getResponse()->getContent());

        $link = $crawler->selectLink("Debug mode")->link();
        $crawler = $client->click($link);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<a class="label label-success" href="/debug">Debug mode</a>', $client->getResponse()->getContent());
    }
}
