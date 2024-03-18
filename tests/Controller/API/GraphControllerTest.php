<?php

namespace App\Tests\Controller\API;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GraphControllerTest extends WebTestCase
{
    public function testDevice1Summary(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/summary/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1Detail(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/detail/1/1/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice1DetailDummy(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/detail/1/3/1');

        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }

    public function testDevice2Summary(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/summary/2');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testDevice3Summary(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/summary/3');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testSlaveLoad(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/slaves/slave1/load');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveMemory(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/slaves/slave1/memory');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlavePosts(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/slaves/slave1/posts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveQueues(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/slaves/slave1/queues');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }

    public function testSlaveWorkers(): void
    {
        $client = static::createClient();
        $userRepository = new UserRepository(static::$container->get('doctrine'));
        $client->loginUser($userRepository->findOneBy(['username' => 'test']), 'api');

        $client->request('GET', '/api/graphs/slaves/slave1/workers');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'image/png'));
    }
}
