<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 9/03/2018
 * Time: 15:01
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Probe;
use AppBundle\Entity\ProbeArchive;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ProbeArchiveFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $probeArchive = new ProbeArchive();
        $probeArchive->setFunction("AVERAGE");
        $probeArchive->setSteps(1);
        $probeArchive->setRows(1000);
        $probeArchive->setProbe($this->getReference('probe-ping'));
        $manager->persist($probeArchive);

        $probeArchive = new ProbeArchive();
        $probeArchive->setFunction("AVERAGE");
        $probeArchive->setSteps(12);
        $probeArchive->setRows(1000);
        $probeArchive->setProbe($this->getReference('probe-ping'));
        $manager->persist($probeArchive);

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            ProbeFixtures::class,
        );
    }
}