<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\Email;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EmailCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Email::class;
    }
}