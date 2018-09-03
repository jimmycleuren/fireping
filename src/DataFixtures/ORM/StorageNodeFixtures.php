<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 5/01/2018
 * Time: 21:57
 */

namespace App\DataFixtures\ORM;

use App\Entity\Probe;
use App\Entity\StorageNode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class StorageNodeFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $node = new StorageNode();
        $node->setIp('127.0.0.1');
        $node->setName('local');
        $node->setStatus(StorageNode::STATUS_ACTIVE);
        $manager->persist($node);

        $manager->flush();
    }
}