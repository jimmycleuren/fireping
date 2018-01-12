<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:11
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Device;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class DeviceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $device = new Device();
        $device->setName('Device 1');
        $device->setIp('8.8.8.8');
        $device->setDomain($this->getReference('domain-1'));
        $manager->persist($device);
        $this->addReference('device-1', $device);

        $device = new Device();
        $device->setName('Device 2');
        $device->setIp('8.8.4.4');
        $device->setDomain($this->getReference('subdomain-2'));
        $device->addProbe($this->getReference('probe-ping'));
        $manager->persist($device);
        $this->addReference('device-2', $device);

        $device = new Device();
        $device->setName('Device 3');
        $device->setIp('8.8.8.8');
        $device->setDomain($this->getReference('domain-3'));
        $manager->persist($device);
        $this->addReference('device-3', $device);

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            DomainFixtures::class,
            ProbeFixtures::class,
        );
    }
}