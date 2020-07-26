<?php

namespace App\DataFixtures\ORM;

use App\Entity\AlertDestination\EmailDestination;
use App\Entity\AlertDestination\LogDestination;
use App\Entity\AlertDestination\SlackDestination;
use App\Entity\AlertDestination\WebhookDestination;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AlertDestinationFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $alertDestination = new LogDestination();
        $alertDestination->setName('syslog');
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-1', $alertDestination);

        $alertDestination = new EmailDestination();
        $alertDestination->setName('mail');
        $alertDestination->setRecipient('test@test.com');
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-mail', $alertDestination);

        $alertDestination = new SlackDestination();
        $alertDestination->setName('slack');
        $alertDestination->setChannel('general');
        $alertDestination->setUrl('https://slack.example');
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-slack', $alertDestination);

        $alertDestination = new WebhookDestination();
        $alertDestination->setName('webhook');
        $alertDestination->setUrl('https://example.tld');
        $manager->persist($alertDestination);

        // @TODO: Remove after fixing ON CASCADE constraints for Domains and Devices. This is only required for the DELETE endpoint test.
        $alertDestination = new SlackDestination();
        $alertDestination->setName('unused-slack');
        $alertDestination->setChannel('general');
        $alertDestination->setUrl('https://slack.example');
        $manager->persist($alertDestination);

        // @TODO: Remove after fixing ON CASCADE constraints for Domains and Devices. This is only required for the DELETE endpoint test.
        $alertDestination = new EmailDestination();
        $alertDestination->setName('unused-mail');
        $alertDestination->setRecipient('user@fireping.example');
        $manager->persist($alertDestination);

        // @TODO: Remove after fixing ON CASCADE constraints for Domains and Devices. This is only required for the DELETE endpoint test.
        $alertDestination = new LogDestination();
        $alertDestination->setName('unused-log');
        $manager->persist($alertDestination);

        // @TODO: Remove after fixing ON CASCADE constraints for Domains and Devices. This is only required for the DELETE endpoint test.
        $alertDestination = new WebhookDestination();
        $alertDestination->setName('unused-webhook');
        $alertDestination->setUrl('https://slack.example');
        $manager->persist($alertDestination);

        $manager->flush();
    }
}
