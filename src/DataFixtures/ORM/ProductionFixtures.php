<?php

namespace App\DataFixtures\ORM;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductionFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('slave');
        $user->setEmail('slave@fireping.be');
        $user->setPassword('$2y$13$6XtlF9vUPlB/S.mtKlUxE.UuGFHoXAMvz2pRiE8G9x/t9ADUFVqi.'); //password
        $user->setEnabled(true);
        $user->setRoles(array('ROLE_API'));
        $manager->persist($user);

        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('admin@fireping.be');
        $user->setPassword('$2y$13$l6j5R7CqOhcqH1zJHjA5xuIymtVQgXmJPUdeahjk3vxKQCmz4vrRG'); //admin
        $user->setEnabled(true);
        $user->setRoles(array('ROLE_ADMIN'));
        $manager->persist($user);


        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['production'];
    }
}