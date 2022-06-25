<?php

namespace App\Tests\Controller\API;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DomainControllerTest extends WebTestCase
{
    public function testCollection()
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/domains.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testNameFilter(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/domains?name=Domain 4', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals(1, $decoded['hydra:totalItems']);
        $this->assertEquals("Domain 4", $decoded['hydra:member'][0]['name']);
    }

    public function testParentNameFilter(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/domains?parent.name=Domain 1', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals(1, $decoded['hydra:totalItems']);
        $this->assertEquals('Subdomain 2', $decoded['hydra:member'][0]['name']);
    }

    public function testAddRemove()
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request(
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

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $client->request('DELETE', "/api/domains/$id.json");

        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testAlerts()
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/domains/1/alerts.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $this->assertEquals('Alertrule 1 on Device 1 from Slavegroup 1', json_decode($response->getContent())[0]->message);
    }
}
