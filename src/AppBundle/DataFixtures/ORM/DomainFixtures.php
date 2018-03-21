<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class DomainFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $domain = new Domain();
        $domain->setId(1);
        $domain->setName("Domain 1");
        $domain->addProbe($this->getReference('probe-ping'));
        $domain->addProbe($this->getReference('probe-dummy'));
        $domain->addSlaveGroup($this->getReference('slavegroup-1'));
        $domain->addAlertRule($this->getReference('alertrule-2'));
        $domain->addAlertDestination($this->getReference('alertdestination-mail'));
        $manager->persist($domain);
        $this->addReference('domain-1', $domain);

        $domain = new Domain();
        $domain->setId(2);
        $domain->setName("Subdomain 2");
        $domain->setParent($this->getReference('domain-1'));
        $manager->persist($domain);
        $this->addReference('subdomain-2', $domain);

        $domain = new Domain();
        $domain->setId(3);
        $domain->setName("Domain 3");
        $domain->addProbe($this->getReference('probe-dummy'));
        $domain->addSlaveGroup($this->getReference('slavegroup-1'));
        $manager->persist($domain);
        $this->addReference('domain-3', $domain);

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            ProbeFixtures::class,
            SlaveGroupFixtures::class,
            AlertRuleFixtures::class,
        );
    }
}