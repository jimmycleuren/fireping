<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class DomainFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $domain = new Domain();
        $domain->setName("Domain 1");
        $manager->persist($domain);

        $manager->flush();
    }
}