<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 9/03/2018
 * Time: 15:01
 */

namespace App\DataFixtures\ORM;

use App\Entity\Probe;
use App\Entity\ProbeArchive;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProbeArchiveFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $probeArchive = new ProbeArchive();
        $probeArchive->setFunction("AVERAGE");
        $probeArchive->setSteps(1);
        $probeArchive->setRows(1440);
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