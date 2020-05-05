<?php


namespace App\Tests\App\Api;

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
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function createAuthorizedClient(): \Symfony\Bundle\FrameworkBundle\Client
    {
        return static::createClient(array(), array(
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW'   => 'test123',
        ));
    }

}