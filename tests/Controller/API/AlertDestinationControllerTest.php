<?php

namespace App\Tests\Controller\API;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertDestinationControllerTest extends WebTestCase
{
    public function testCollection()
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/alert_destinations.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testAddRemove()
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request(
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

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $client->request('DELETE', "/api/alert_destinations/$id.json");

        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }
}
