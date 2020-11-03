<?php

namespace App\Controller\Admin;

use App\Entity\Alert;
use App\Entity\AlertDestination\EmailDestination;
use App\Entity\AlertDestination\LogDestination;
use App\Entity\AlertDestination\SlackDestination;
use App\Entity\AlertDestination\WebhookDestination;
use App\Entity\AlertRule;
use App\Entity\Device;
use App\Entity\Domain;
use App\Entity\Probe;
use App\Entity\ProbeArchive;
use App\Entity\Slave;
use App\Entity\SlaveGroup;
use App\Entity\StorageNode;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Fireping');
    }

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Domain', 'fas fa-folder-open', Domain::class);
        yield MenuItem::linkToCrud('Device', 'fas fa-folder-open', Device::class);
        yield MenuItem::linkToCrud('Alert', 'fas fa-folder-open', Alert::class);
        yield MenuItem::linkToCrud('AlertRule', 'fas fa-folder-open', AlertRule::class);
        yield MenuItem::linkToCrud('Probe', 'fas fa-folder-open', Probe::class);
        yield MenuItem::linkToCrud('ProbeArchive', 'fas fa-folder-open', ProbeArchive::class);
        yield MenuItem::linkToCrud('Slave', 'fas fa-folder-open', Slave::class);
        yield MenuItem::linkToCrud('SlaveGroup', 'fas fa-folder-open', SlaveGroup::class);
        yield MenuItem::linkToCrud('StorageNode', 'fas fa-folder-open', StorageNode::class);
        yield MenuItem::linkToCrud('User', 'fas fa-folder-open', User::class);

        yield MenuItem::section('Alert Destinations');
        yield MenuItem::linkToCrud('Slack', 'fas fa-folder-open', SlackDestination::class);
        yield MenuItem::linkToCrud('Email', 'fas fa-folder-open', EmailDestination::class);
        yield MenuItem::linkToCrud('Webhook', 'fas fa-folder-open', WebhookDestination::class);
        yield MenuItem::linkToCrud('Logging', 'fas fa-folder-open', LogDestination::class);
    }
}
