<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\SlackDestination;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SlackDestinationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SlackDestination::class;
    }
}