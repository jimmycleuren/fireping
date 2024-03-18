<?php

namespace App\Tests\Controller\API;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertRuleControllerTest extends WebTestCase
{
    public function testCollection(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/alert_rules.json', [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());
    }

    public function testAddRemove(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request(
            'POST',
            '/api/alert_rules.json',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'name' => 'rule',
                'datasource' => 'loss',
                'pattern' => '=0,>0,>0',
                'probe' => '/api/probes/1',
                'parent' => '/api/alert_rules/1',
                'messageUp' => 'Device up',
                'messageDown' => 'Device down',
            ])
        );

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $client->request('DELETE', "/api/alert_rules/$id.json");

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }
}
