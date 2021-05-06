<?php

namespace App\Tests\Controller\API;

class DomainApiTest extends AbstractApiTest
{
    public function testCollection()
    {
        $crawler = $this->client->request('GET', '/api/domains.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testAddRemove()
    {
        $crawler = $this->client->request(
            'POST',
            '/api/domains.json',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'name' => 'New Domain',
            ])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $crawler = $this->client->request(
            'DELETE',
            "/api/domains/$id.json"
        );

        $response = $this->client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testAlerts()
    {
        $crawler = $this->client->request('GET', '/api/domains/1/alerts.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $this->assertEquals('Alertrule 1 on Device 1 from Slavegroup 1', json_decode($response->getContent())[0]->message);
    }
}
