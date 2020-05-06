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
        $link =$crawler->filter('.label-danger');
        $this->assertEquals('a', $link->nodeName());

        $link = $crawler->selectLink('Display Trends')->link();
        $crawler = $client->click($link);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $link =$crawler->filter('.label-success');
        $this->assertEquals('a', $link->nodeName());
    }
}
