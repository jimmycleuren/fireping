<?php

namespace App\Tests\App\Controller;

use App\DependencyInjection\StatsManager;
use App\Tests\App\Api\AbstractApiTest;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class SlaveControllerTest extends AbstractApiTest
{
    use ProphecyTrait;

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/slaves');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Slaves', $crawler->filter('h1')->text());
    }

    public function testDetail()
    {
        $crawler = $this->client->request('GET', '/slaves/slave1');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Slave slave1', $crawler->filter('h1')->text());
    }

    public function testError()
    {
        $this->client->request('POST', '/api/slaves/slave1/error', ['message' => 'error']);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfig()
    {
        $this->client->request('GET', '/api/slaves/slave1/config');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigUnuedSlave()
    {
        $this->client->request('GET', '/api/slaves/slave-unused/config');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigNewSlave()
    {
        $id = date('U');

        $this->client->request('GET', '/api/slaves/'.$id.'/config');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $this->assertNotNull($em->getRepository('App:Slave')->findOneById($id));
    }

    public function testEmptyResult()
    {
        $this->client->request('POST', '/api/slaves/slave1/result');

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultWithoutTimestamp()
    {
        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultInvalidFormat()
    {
        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => date('U'),
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultUnknownTarget()
    {
        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => date('U'),
                'targets' => [
                    '1000' => [],
                ],
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingWrongStep()
    {
        $timestamp = date('U');

        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => $timestamp,
                'targets' => [
                    '1' => [
                        0 => 1,
                    ],
                ],
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingUnreachable()
    {
        $timestamp = date('U');

        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => $timestamp,
                'targets' => [
                    '1' => [
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
                    ],
                ],
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($this->client->getContainer()->get('kernel')->getProjectDir().'/var/rrd/1/1/1.rrd');
    }

    public function testResultPing()
    {
        $timestamp = date('U');

        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => $timestamp,
                'targets' => [
                    '1' => [
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
                    ],
                ],
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        //try to update at the same timestamp again
        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => $timestamp,
                'targets' => [
                    '1' => [
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
                    ],
                ],
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($this->client->getContainer()->get('kernel')->getProjectDir().'/var/rrd/1/1/1.rrd');
    }

    public function testResultTracerouteWrongStep()
    {
        $timestamp = date('U');

        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '2' => [
                'timestamp' => $timestamp,
                'targets' => [
                    '1' => [
                        'hop' => [
                            0 => [
                                'ip' => '1.1.1.1',
                                'latencies' => [1],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultTraceroute()
    {
        $timestamp = date('U');

        $this->client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '2' => [
                'timestamp' => $timestamp,
                'targets' => [
                    '1' => [
                        'hop' => [
                            0 => [
                                'ip' => '1.1.1.1',
                                'latencies' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testStats()
    {
        exec('rm -rf '.$this->client->getContainer()->get('kernel')->getProjectDir().'/var/rrd/slaves');

        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());
        $statsManager->addQueueItems(0, 5);
        $statsManager->addWorkerStats(10, 5, ['ping' => 5]);

        $this->client->request('POST', '/api/slaves/slave1/stats', [], [], [], json_encode($statsManager->getStats()));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        //second call for updates with additional datasource
        sleep(1);
        $statsManager->addWorkerStats(10, 5, ['ping' => 5, 'traceroute' => 1]);
        $this->client->request('POST', '/api/slaves/slave1/stats', [], [], [], json_encode($statsManager->getStats()));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }
}
