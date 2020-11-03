<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlertDestination\AlertDestination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AlertDestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertDestination::class);
    }
}
