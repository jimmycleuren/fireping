<?php
declare(strict_types=1);

namespace App\Tests\Controller\API;

use App\Repository\UserRepository;
use App\Slave\Worker\StatsManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SlaveControllerTest extends WebTestCase
{
    public function testError(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/error', ['message' => 'error']);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfig(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/slaves/slave1/config');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigUnusedSlave(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/slaves/slave-unused/config');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testConfigNewSlave(): void
    {
        $id = date('U');

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/slaves/'.$id.'/config');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        $em = $client->getContainer()->get('doctrine')->getManager();
        $this->assertNotNull($em->getRepository('App:Slave')->findOneById($id));
    }

    public function testEmptyResult(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result');

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultWithoutTimestamp(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [],
        ]));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultInvalidFormat(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => date('U'),
            ],
        ]));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultUnknownTarget(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => date('U'),
                'targets' => [
                    '1000' => [],
                ],
            ],
        ]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingWrongStep(): void
    {
        $timestamp = date('U');

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
            '1' => [
                'timestamp' => $timestamp,
                'targets' => [
                    '1' => [
                        0 => 1,
                    ],
                ],
            ],
        ]));

        $response = $client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultPingUnreachable(): void
    {
        $timestamp = date('U');

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
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

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($client->getContainer()->get('kernel')->getProjectDir().'/var/rrd/1/1/1.rrd');
    }

    public function testResultPing(): void
    {
        $timestamp = date('U');

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
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

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        //try to update at the same timestamp again
        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
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

        $response = $client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        unlink($client->getContainer()->get('kernel')->getProjectDir().'/var/rrd/1/1/1.rrd');
    }

    public function testResultTracerouteWrongStep(): void
    {
        $timestamp = date('U');

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
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

        $response = $client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultTraceroute(): void
    {
        $timestamp = date('U');

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode([
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

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultHttp(): void
    {
        $timestamp = date("U");

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode(['4' => ['timestamp' => $timestamp, 'targets' => ['1' => [0 => ['time' => -1, 'code' => -1], 1 => ['time' => -1, 'code' => -1], 2 => ['time' => 100, 'code' => 200], 3 => ['time' => 100, 'code' => 200], 4 => ['time' => 100, 'code' => 200]]]]]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultHttpIncorrectSampleCount(): void
    {
        $timestamp = date("U") + 1;

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode(['4' => ['timestamp' => $timestamp, 'targets' => ['1' => [0 => ['time' => -1, 'code' => -1], 1 => ['time' => -1, 'code' => -1]]]]]));

        $response = $client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultHttpUnreachable(): void
    {
        $timestamp = date("U") + 2;

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode(['4' => ['timestamp' => $timestamp, 'targets' => ['1' => [0 => ['time' => -1, 'code' => -1], 1 => ['time' => -1, 'code' => -1], 2 => ['time' => -1, 'code' => -1], 3 => ['time' => -1, 'code' => -1], 4 => ['time' => -1, 'code' => -1]]]]]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultTracerouteNoIp(): void
    {
        $timestamp = date("U");

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode(['2' => ['timestamp' => $timestamp, 'targets' => ['1' => ['hop' => [0 => ['latencies' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]]]]]]]));

        $response = $client->getResponse();
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testResultTracerouteUnreachable(): void
    {
        $timestamp = date("U")+1;

        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('POST', '/api/slaves/slave1/result', [], [], [], json_encode(['2' => ['timestamp' => $timestamp, 'targets' => ['1' => ['hop' => [0 => ['ip' => "1.1.1.1", 'latencies' => [-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1]]]]]]]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testStats(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        exec('rm -rf '.$client->getContainer()->get('kernel')->getProjectDir().'/var/rrd/slaves');

        $logger = $this->prophesize(LoggerInterface::class);
        $statsManager = new StatsManager($logger->reveal());
        $statsManager->addQueueItems(0, 5);
        $statsManager->addWorkerStats(10, 5, ['ping' => 5]);

        $client->request('POST', '/api/slaves/slave1/stats', [], [], [], json_encode($statsManager->getStats()));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        //second call for updates with additional datasource
        sleep(1);
        $statsManager->addWorkerStats(10, 5, ['ping' => 5, 'traceroute' => 1]);
        $client->request('POST', '/api/slaves/slave1/stats', [], [], [], json_encode($statsManager->getStats()));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
    }

    public function testHealth(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'slave01']), 'api');

        $client->request('GET', '/api/slaves/health');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
