<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 21:45.
 */

namespace Tests\App\Api;

use App\Tests\App\Api\AbstractApiTest;

class AlertRuleApiTest extends AbstractApiTest
{
    public function testCollection()
    {
        $crawler = $this->client->request('GET', '/api/alert_rules.json', [], [], [
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

        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $crawler = $this->client->request(
            'DELETE',
            "/api/alert_rules/$id.json"
        );

        $response = $this->client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }
}
