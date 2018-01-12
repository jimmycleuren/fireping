<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:04
 */

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SlaveControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/slaves');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Slave list', $crawler->filter('h1')->text());
    }

    public function testError()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/api/slaves/slave1/error', array("message" => "error"));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testConfig()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/slaves/slave1/config');

        $this->assertJsonResponse($client->getResponse());
    }

    public function testConfigUnuedSlave()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/slaves/slave-unused/config');

        $this->assertJsonResponse($client->getResponse());
    }

    public function testConfigNewSlave()
    {
        $client = static::createClient();
        $id = date("U");

        $crawler = $client->request('GET', '/api/slaves/'.$id.'/config');

        $this->assertJsonResponse($client->getResponse());

        $em = $client->getContainer()->get('doctrine')->getManager();
        $this->assertNotNull($em->getRepository("AppBundle:Slave")->findOneById($id));
    }

    /**
     * @param $response
     * @param int  $statusCode
     * @param bool $checkValidJson
     *                             Assert that the response is in json format
     */
    protected function assertJsonResponse($response, $statusCode = 200, $checkValidJson = true)
    {
        $this->assertEquals(
            $statusCode,
            $response->getStatusCode(),
            $response->getContent()
        );
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            $response->headers
        );

        if ($checkValidJson) {
            $decode = json_decode($response->getContent());
            $this->assertTrue(
                ($decode !== false),
                $response->getContent()
            );
        }
    }
}
