<?php

namespace App\DataFixtures\ORM;

use App\Entity\Device;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

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

        $device = new Device();
        $device->setName('Device 4');
        $device->setIp('8.8.8.8');
        $device->setDomain($this->getReference('domain-4'));
        $manager->persist($device);
        $this->addReference('device-4', $device);

        $device = new Device();
        $device->setName('Device 5');
        $device->setIp('8.8.8.8');
        $device->setDomain($this->getReference('domain-4'));
        $device->addSlaveGroup($this->getReference('slavegroup-1'));
        $manager->persist($device);
        $this->addReference('device-5', $device);

        $device = new Device();
        $device->setName('Device 6');
        $device->setIp('8.8.8.8');
        $device->setDomain($this->getReference('domain-4'));
        $device->addSlaveGroup($this->getReference('slavegroup-2'));
        $device->addAlertRule($this->getReference('alertrule-1'));
        $device->addProbe($this->getReference('probe-ping'));
        $manager->persist($device);
        $this->addReference('device-6', $device);

        $device = new Device();
        $device->setName('Device 7');
        $device->setIp('8.8.8.8');
        $device->setDomain($this->getReference('domain-4'));
        $device->addSlaveGroup($this->getReference('slavegroup-2'));
        $device->addAlertRule($this->getReference('alertrule-1'));
        $device->addProbe($this->getReference('probe-traceroute'));
        $manager->persist($device);
        $this->addReference('device-7', $device);

        $device = new Device();
        $device->setName('Device 8');
        $device->setIp('8.8.8.8');
        $device->setDomain($this->getReference('domain-4'));
        $device->addProbe($this->getReference('probe-ping'));
        $manager->persist($device);
        $this->addReference('device-8', $device);


        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            DomainFixtures::class,
            ProbeFixtures::class,
        ];
    }
}
