<?php

namespace App\Tests\Controller\API;

class DeviceControllerTest extends BaseControllerTestCase
{
    public function testCollection()
    {
        $crawler = $this->client->request('GET', '/api/devices.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testStatus()
    {
        $crawler = $this->client->request('GET', '/api/devices/1/status.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $this->assertEquals('unknown', json_decode($response->getContent())->status);
    }

    public function testStatusNoProbe()
    {
        $crawler = $this->client->request('GET', '/api/devices/3/status.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $this->assertEquals('No ping probe assigned', json_decode($response->getContent())->message);
    }
}
