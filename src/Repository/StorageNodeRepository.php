<?php

namespace App\Repository;

use App\Entity\StorageNode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StorageNode|null find($id, $lockMode = null, $lockVersion = null)
 * @method StorageNode|null findOneBy(array $criteria, array $orderBy = null)
 * @method StorageNode[]    findAll()
 * @method StorageNode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorageNodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorageNode::class);
    }
}
