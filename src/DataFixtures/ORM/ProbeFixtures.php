<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:57
 */

namespace App\DataFixtures\ORM;

use App\Entity\Probe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

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
        $this->addReference('probe-ping', $probe);

        $probe = new Probe();
        $probe->setName("Traceroute");
        $probe->setStep(60);
        $probe->setSamples(15);
        $probe->setType('traceroute');
        $probe->setArguments("");
        $manager->persist($probe);
        $this->addReference('probe-traceroute', $probe);

        $probe = new Probe();
        $probe->setName("Dummy");
        $probe->setStep(60);
        $probe->setSamples(15);
        $probe->setType('dummy');
        $probe->setArguments("");
        $manager->persist($probe);
        $this->addReference('probe-dummy', $probe);

        $manager->flush();
    }
}