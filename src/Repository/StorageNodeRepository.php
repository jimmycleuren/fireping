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

//    /**
//     * @return StorageNode[] Returns an array of StorageNode objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StorageNode
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
