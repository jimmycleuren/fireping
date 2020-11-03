<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\EmailDestination;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EmailDestinationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailDestination::class;
    }
}