<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 21:45
 */

namespace Tests\App\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlertRuleApiTest extends WebTestCase
{
    public function testCollection()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/alert_rules.json', array(), array(), array(
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
            '/api/alert_rules.json',
            array(),
            array(),
            array(
                "CONTENT_TYPE" => "application/json"
            ),
            json_encode(array(
                'name' => "rule",
                'datasource' => 'loss',
                'pattern' => '=0,>0,>0',
                'probe' => '/api/probes/1',
                'parent' => '/api/alert_rules/1',
            ))
        );

        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json; charset=utf-8'));
        $this->assertJson($response->getContent());

        $id = json_decode($response->getContent())->id;

        $crawler = $client->request(
            'DELETE',
            "/api/alert_rules/$id.json"
        );

        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }
}