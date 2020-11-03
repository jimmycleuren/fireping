<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\WebhookDestination;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class WebhookDestinationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return WebhookDestination::class;
    }
}