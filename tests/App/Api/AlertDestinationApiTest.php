<?php

namespace Tests\App\Api;

use App\Tests\App\Api\AbstractApiTest;

class AlertDestinationApiTest extends AbstractApiTest
{
    public function testCollection()
    {
        $this->client->request('GET', '/api/alert_destinations.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testAddRemove()
    {
        $this->client->request(
            'POST',
            '/api/alert_destinations.json',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'name' => 'syslogtest',
                'type' => 'syslog',
                'parameters' => [],
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $crawler = $this->client->request(
            'DELETE',
            "/api/alert_destinations/$id.json"
        );

        $response = $this->client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }
}
