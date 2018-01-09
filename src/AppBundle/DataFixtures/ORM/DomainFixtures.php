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
        $manager->persist($domain);

        $manager->flush();

        $this->addReference('domain-1', $domain);
    }

    public function getDependencies()
    {
        return array(
            ProbeFixtures::class,
        );
    }
}