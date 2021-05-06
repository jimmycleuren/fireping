<?php

namespace App\Tests\Controller\API;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseControllerTestCase extends WebTestCase
{
    protected $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthorizedClient();
    }

    protected function asSlave(): KernelBrowser
    {
        $client = clone $this->client;

        $client->setServerParameters([
            'PHP_AUTH_USER' => 'slave01',
            'PHP_AUTH_PW' => 'test123',
        ]);

        return $client;
    }

    /**
     * Create an HTTP authorized client.
     */
    protected function createAuthorizedClient(): KernelBrowser
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW' => 'test123',
        ]);
    }
}
