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

    public function testFilledDatabaseInit()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/database-init');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    /**
     * Drop en recreate to have an empty database
     */
    public function testEmptyDatabaseInit()
    {
        $client = static::createClient();

        ob_start();
        passthru(sprintf(
            'php "%s/../../../bin/console" doctrine:schema:drop --env=test --force',
            __DIR__
        ));
        passthru(sprintf(
            'php "%s/../../../bin/console" doctrine:schema:create --env=test',
            __DIR__
        ));
        ob_end_clean();

        $crawler = $client->request('GET', '/database-init');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        ob_start();
        passthru(sprintf(
            'php "%s/../../../bin/console" doctrine:schema:drop --env=test --force',
            __DIR__
        ));
        passthru(sprintf(
            'php "%s/../../../bin/console" doctrine:schema:create --env=test',
            __DIR__
        ));

        passthru(sprintf(
            'php "%s/../../../bin/console" doctrine:fixtures:load -n --env=test',
            __DIR__
        ));
        ob_end_clean();
    }
}
