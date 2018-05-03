<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 12/01/2018
 * Time: 22:48
 */

namespace Tests\App\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeviceApiTest extends WebTestCase
{
    public function testCollection()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/devices.json', array(), array(), array(
            "HTTP_Accept" => "application/json"
        ));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testStatus()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/devices/1/status.json', array(), array(), array(
            "HTTP_Accept" => "application/json"
        ));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $this->assertEquals('unknown', json_decode($response->getContent())->status);
    }

    public function testStatusNoProbe()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/devices/3/status.json', array(), array(), array(
            "HTTP_Accept" => "application/json"
        ));

        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $this->assertEquals('No ping probe assigned', json_decode($response->getContent())->message);
    }
}
