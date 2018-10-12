<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:04
 */

namespace Tests\App\Controller;

use App\Tests\App\Api\AbstractApiTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SlaveControllerTest extends AbstractApiTest
{
    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/slaves');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Slave list', $crawler->filter('h1')->text());
    }

    public function testError()
    {
        $crawler = $this->client->request('POST', '/api/slaves/slave1/error', array("message" => "error"));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfig()
    {
        $crawler = $this->client->request('GET', '/api/slaves/slave1/config');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigUnuedSlave()
    {
        $crawler = $this->client->request('GET', '/api/slaves/slave-unused/config');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigNewSlave()
    {
        $id = date("U");

        $crawler = $this->client->request('GET', '/api/slaves/'.$id.'/config');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $this->assertNotNull($em->getRepository("App:Slave")->findOneById($id));
    }

    public function testEmptyResult()
    {
        $crawler = $this->client->request('POST', '/api/slaves/slave1/result');

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultWithoutTimestamp()
    {
        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array()
        )));

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultInvalidFormat()
    {
        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => date("U")
            )
        )));

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultUnknownTarget()
    {
        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => date("U"),
                'targets' => array(
                    '1000' => array()
                )
            )
        )));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingWrongStep()
    {
        $timestamp = date("U");

        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '1' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => 1,
                    )
                )
            )
        )));

        $response = $this->client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingUnreachable()
    {
        $timestamp = date("U");

        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
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

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($this->client->getContainer()->get('kernel')->getProjectDir()."/var/rrd/1/1/1.rrd");
    }

    public function testResultPing()
    {
        $timestamp = date("U");

        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
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

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        //try to update at the same timestamp again
        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
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

        $response = $this->client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($this->client->getContainer()->get('kernel')->getProjectDir()."/var/rrd/1/1/1.rrd");
    }

    public function testResultTracerouteWrongStep()
    {
        $timestamp = date("U");

        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
            '2' => array(
                'timestamp' => $timestamp,
                'targets' => array(
                    '1' => array(
                        0 => 1,
                    )
                )
            )
        )));

        $response = $this->client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultTraceroute()
    {
        $timestamp = date("U");

        $crawler = $this->client->request('POST', '/api/slaves/slave1/result', array(), array(), array(), json_encode(array(
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

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }
}
