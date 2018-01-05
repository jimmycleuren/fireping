<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:57
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Probe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ProbeFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $probe = new Probe();
        $probe->setName("Ping");
        $probe->setStep(60);
        $probe->setSamples(15);
        $probe->setType('ping');
        $probe->setArguments("");
        $manager->persist($probe);

        $manager->flush();

        $this->addReference('probe-ping', $probe);
    }
}