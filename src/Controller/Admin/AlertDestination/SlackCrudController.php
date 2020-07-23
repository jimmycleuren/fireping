<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\Slack;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SlackCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Slack::class;
    }
}