<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\LogDestination;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class LogDestinationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LogDestination::class;
    }
}