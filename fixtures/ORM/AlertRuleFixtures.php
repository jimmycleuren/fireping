<?php

namespace App\DataFixtures\ORM;

use App\Entity\AlertRule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AlertRuleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $alertrule = new AlertRule();
        $alertrule->setName('Alertrule 1');
        $alertrule->setDatasource('loss');
        $alertrule->setPattern('>0,>0');
        $alertrule->setMessageUp('Device up');
        $alertrule->setMessageDown('Device down');
        $alertrule->setProbe($this->getReference('probe-ping'));
        $manager->persist($alertrule);
        $this->addReference('alertrule-1', $alertrule);

        $alertrule = new AlertRule();
        $alertrule->setName('Alertrule 2');
        $alertrule->setDatasource('loss');
        $alertrule->setPattern('>0');
        $alertrule->setMessageUp('Device up');
        $alertrule->setMessageDown('Device down');
        $alertrule->setProbe($this->getReference('probe-ping'));
        $manager->persist($alertrule);
        $this->addReference('alertrule-2', $alertrule);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            ProbeFixtures::class,
        ];
    }
}
