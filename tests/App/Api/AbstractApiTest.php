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
        $client = static::createClient();
        $container = $client->getContainer();
        $session = $container->get('session');
        $firewall= 'main';
        $userManager = static::$kernel->getContainer()->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('username' => 'test'));
        $token = new UsernamePasswordToken($user, $user->getPassword(), $firewall, $user->getRoles());

        // save the login token into the session and put it in a cookie
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $client;
    }

}