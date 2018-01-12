<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:51
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\SlaveGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class SlaveGroupFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $slavegroup = new SlaveGroup();
        $slavegroup->setName('Slavegroup 1');
        $manager->persist($slavegroup);
        $this->addReference('slavegroup-1', $slavegroup);

        $slavegroup = new SlaveGroup();
        $slavegroup->setName('Unused SlaveGroup');
        $manager->persist($slavegroup);
        $this->addReference('slavegroup-unused', $slavegroup);

        $manager->flush();
    }
}