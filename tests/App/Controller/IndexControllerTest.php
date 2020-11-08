<?php

namespace Tests\App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Fireping', $crawler->filter('.logo-lg')->text());
    }

    public function testIndexAsAdmin()
    {
        $client = static::createClient();

        $userRepository = static::$container->get(UserRepository::class);
        $testUser = $userRepository->findOneByUsername('test');
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Fireping', $crawler->filter('.logo-lg')->text());
    }

    public function testDatabaseInit()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/database-init');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
