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

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfig()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/slaves/slave1/config');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigUnuedSlave()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/slaves/slave-unused/config');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigNewSlave()
    {
        $client = static::createClient();
        $id = date("U");

        $crawler = $client->request('GET', '/api/slaves/'.$id.'/config');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $em = $client->getContainer()->get('doctrine')->getManager();
        $this->assertNotNull($em->getRepository("AppBundle:Slave")->findOneById($id));
    }

    public function testEmptyResult()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/api/slaves/slave1/result');

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultWithoutTimestamp()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array()
        )));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultInvalidFormat()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => date("U")
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultUnknownTarget()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => date("U"),
                'targets' => array(
                    '1000' => array()
                )
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingWrongStep()
    {
        $client = static::createClient();
        $timestamp = date("U");

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => 1,
                    )
                )
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingUnreachable()
    {
        $client = static::createClient();
        $timestamp = date("U");

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => -1,
                        1 => -1,
                        2 => -1,
                        3 => -1,
                        4 => -1,
                        5 => -1,
                        6 => -1,
                        7 => -1,
                        8 => -1,
                        9 => -1,
                        10 => -1,
                        11 => -1,
                        12 => -1,
                        13 => -1,
                        14 => -1,
                    )
                )
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($client->getContainer()->get('kernel')->getProjectDir()."/var/rrd/1/1/1.rrd");
    }

    public function testResultPing()
    {
        $client = static::createClient();
        $timestamp = date("U");

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => 1,
                        1 => 1,
                        2 => 1,
                        3 => 1,
                        4 => 1,
                        5 => 1,
                        6 => 1,
                        7 => 1,
                        8 => 1,
                        9 => 1,
                        10 => 1,
                        11 => 1,
                        12 => 1,
                        13 => -1,
                        14 => -1,
                    )
                )
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        //try to update at the same timestamp again
        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => 1,
                        1 => 1,
                        2 => 1,
                        3 => 1,
                        4 => 1,
                        5 => 1,
                        6 => 1,
                        7 => 1,
                        8 => 1,
                        9 => 1,
                        10 => 1,
                        11 => 1,
                        12 => 1,
                        13 => 1,
                        14 => 1,
                    )
                )
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($client->getContainer()->get('kernel')->getProjectDir()."/var/rrd/1/1/1.rrd");
    }

    public function testResultTracerouteWrongStep()
    {
        $client = static::createClient();
        $timestamp = date("U");

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '2' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => 1,
                    )
                )
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultTraceroute()
    {
        $client = static::createClient();
        $timestamp = date("U");

        $crawler = $client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '2' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => 1,
                        1 => 1,
                        2 => 1,
                        3 => 1,
                        4 => 1,
                        5 => 1,
                        6 => 1,
                        7 => 1,
                        8 => 1,
                        9 => 1,
                        10 => 1,
                        11 => 1,
                        12 => 1,
                        13 => 1,
                        14 => 1,
                    )
                )
            )
        )));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }
}
