<?php

namespace App\DataFixtures\ORM;

use App\Entity\StorageNode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

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
