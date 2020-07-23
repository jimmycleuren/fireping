<?php

namespace App\DataFixtures\ORM;

use App\Entity\AlertDestination\AlertDestination;
use App\Entity\AlertDestination\Email;
use App\Entity\AlertDestination\Logging;
use App\Entity\AlertDestination\Slack;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AlertDestinationFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $alertDestination = new Logging();
        $alertDestination->setName('syslog');
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-1', $alertDestination);

        $alertDestination = new Email();
        $alertDestination->setName('mail');
        $alertDestination->setRecipient('test@test.com');
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-mail', $alertDestination);

        $alertDestination = new Slack();
        $alertDestination->setName('slack');
        $alertDestination->setChannel('general');
        $alertDestination->setUrl('https://slack.example');
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-slack', $alertDestination);

        $manager->flush();
    }
}
