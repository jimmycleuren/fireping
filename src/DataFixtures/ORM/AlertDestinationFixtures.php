<?php

namespace App\DataFixtures\ORM;

use App\Entity\AlertDestination\AlertDestination;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AlertDestinationFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $alertDestination = new AlertDestination();
        $alertDestination->setName('syslog');
        $alertDestination->setType('syslog');
        $alertDestination->setParameters([]);
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-1', $alertDestination);

        $alertDestination = new AlertDestination();
        $alertDestination->setName('mail');
        $alertDestination->setType('mail');
        $alertDestination->setParameters(['recipient' => 'test@test.com']);
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-mail', $alertDestination);

        $alertDestination = new AlertDestination();
        $alertDestination->setName('slack');
        $alertDestination->setType('slack');
        $alertDestination->setParameters(['token' => 'token', 'channel' => 'general']);
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-slack', $alertDestination);

        $manager->flush();
    }
}
