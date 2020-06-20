<?php


namespace App\Tests\App\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApiTest extends WebTestCase
{
    protected $client;

    public function setUp() : void
    {
        $this->client = $this->createAuthorizedClient();
    }

    /**
     * Create an HTTP authorized client
     *
     * @return KernelBrowser
     */
    protected function createAuthorizedClient(): KernelBrowser
    {
        return static::createClient(array(), array(
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW'   => 'test123',
        ));
    }

}