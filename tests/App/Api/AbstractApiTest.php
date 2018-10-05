<?php


namespace App\Tests\App\Api;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class AbstractApiTest extends WebTestCase
{
    protected $client = null;

    public function setUp()
    {
       $this->client = $this->createAuthorizedClient();
    }

    protected function createAuthorizedClient()
    {
        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW'   => 'test123',
        ));
        return $client;


    }

}