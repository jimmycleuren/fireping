<?php

declare(strict_types=1);

namespace App\Controller\Admin\AlertDestination;

use App\Entity\AlertDestination\Webhook;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class WebhookCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Webhook::class;
    }
}