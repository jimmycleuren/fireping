<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 08/03/2018
 * Time: 22:48
 */

namespace Tests\App\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertDestinationApiTest extends WebTestCase
{
    public function testCollection()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/alert_destinations.json', array(), array(), array(
            "HTTP_Accept" => "application/json"
        ));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testAddRemove()
    {
        $client = static::createClient();

        $crawler = $client->request(
            'POST',
            '/api/alert_destinations.json',
            array(),
            array(),
            array(
                "CONTENT_TYPE" => "application/json"
            ),
            json_encode(array(
                'name' => "syslogtest",
                'type' => 'syslog',
                'parameters' => json_encode(array()),
            ))
        );

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $crawler = $client->request(
            'DELETE',
            "/api/alert_destinations/$id.json"
        );

        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }
}
