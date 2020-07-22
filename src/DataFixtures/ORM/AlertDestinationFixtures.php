<?php

namespace App\DataFixtures\ORM;

use App\Entity\AlertDestination;
use App\Model\Parameter\AlertDestination\MailParameters;
use App\Model\Parameter\AlertDestination\MonologParameters;
use App\Model\Parameter\AlertDestination\SlackParameters;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Psr7\Uri;

class AlertDestinationFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $alertDestination = new AlertDestination();
        $alertDestination->setName('syslog');
        $alertDestination->setType(AlertDestination::TYPE_LOG);
        $alertDestination->setParameters(new MonologParameters());
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-1', $alertDestination);

        $alertDestination = new AlertDestination();
        $alertDestination->setName('mail');
        $alertDestination->setType(AlertDestination::TYPE_MAIL);
        $alertDestination->setParameters(new MailParameters('test@test.com'));
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-mail', $alertDestination);

        $alertDestination = new AlertDestination();
        $alertDestination->setName('slack');
        $alertDestination->setType(AlertDestination::TYPE_SLACK);
        $alertDestination->setParameters(new SlackParameters('general', new Uri('https://example.example')));
        $manager->persist($alertDestination);
        $this->addReference('alertdestination-slack', $alertDestination);

        $manager->flush();
    }
}
