<?php

namespace App\DataFixtures\ORM;

use App\Entity\ProbeArchive;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProbeArchiveFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $probeArchive = new ProbeArchive();
        $probeArchive->setFunction('AVERAGE');
        $probeArchive->setSteps(1);
        $probeArchive->setRows(1440);
        $probeArchive->setProbe($this->getReference('probe-ping'));
        $manager->persist($probeArchive);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            ProbeFixtures::class,
        ];
    }
}
