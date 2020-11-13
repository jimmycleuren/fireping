<?php

namespace App\Tests\App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EasyAdminControllerTest extends WebTestCase
{
    public function testUserList()
    {
        $client = static::createClient();

        $userRepository = static::$container->get(UserRepository::class);
        $testUser = $userRepository->findOneByUsername('test');
        $client->loginUser($testUser);

        $client->request('GET', '/admin/?entity=User&action=list');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testUserNew()
    {
        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW'   => 'test123',
        ));

        $crawler = $client->request('GET', '/admin/?entity=User&action=new');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Save changes')->form();

        $client->submit($form, [
            'user[username]'    => 'easyadmin',
            'user[email]' => 'a@b.c',
            'user[plainPassword]' => 'bla'
        ]);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testUserUpdate()
    {
        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW'   => 'test123',
        ));

        $entity = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneByUsername("easyadmin");

        $crawler = $client->request('GET', '/admin/?entity=User&action=edit&id='.$entity->getId());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Save changes')->form();

        $client->submit($form, [
            'user[username]'    => 'easyadmin',
            'user[email]' => 'a@b.c',
            'user[plainPassword]' => 'blabla'
        ]);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
