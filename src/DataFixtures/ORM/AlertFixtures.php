<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:05
 */

namespace App\DataFixtures\ORM;

use App\Entity\Alert;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class AlertFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-1'));
        $alert->setSlaveGroup($this->getReference('slavegroup-1'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        //alerts for cleanupAlert
        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-4'));
        $alert->setSlaveGroup($this->getReference('slavegroup-1'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-4'));
        $alert->setSlaveGroup($this->getReference('slavegroup-1'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-4'));
        $alert->setSlaveGroup($this->getReference('slavegroup-1'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-5'));
        $alert->setSlaveGroup($this->getReference('slavegroup-1'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-6'));
        $alert->setSlaveGroup($this->getReference('slavegroup-1'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-6'));
        $alert->setSlaveGroup($this->getReference('slavegroup-2'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        $alert = new Alert();
        $alert->setActive(true);
        $alert->setFirstseen(new \DateTime("1 hour ago"));
        $alert->setLastseen(new \DateTime("now"));
        $alert->setDevice($this->getReference('device-7'));
        $alert->setSlaveGroup($this->getReference('slavegroup-2'));
        $alert->setAlertRule($this->getReference('alertrule-1'));
        $manager->persist($alert);

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            DeviceFixtures::class,
            SlaveGroupFixtures::class,
            AlertRuleFixtures::class
        );
    }
}