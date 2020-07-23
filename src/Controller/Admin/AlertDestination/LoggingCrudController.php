<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\Logging;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class LoggingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Logging::class;
    }
}