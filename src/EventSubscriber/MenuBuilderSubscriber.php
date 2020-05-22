<?php

namespace App\EventSubscriber;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use KevinPapst\AdminLTEBundle\Event\BreadcrumbMenuEvent;
use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuBuilderSubscriber implements EventSubscriberInterface
{
    private $domainRepository;

    public function __construct(DomainRepository $domainRepository)
    {
        $this->domainRepository = $domainRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SidebarMenuEvent::class => ['onSetupMenu', 100],
            BreadcrumbMenuEvent::class => ['onSetupBreadcrumbMenu', 100],
        ];
    }

    private function addStaticMenu(SidebarMenuEvent $event)
    {
        $slaves = new MenuItemModel('slaves', "Slaves", "slave_index", [], 'fas fa-tachometer-alt');
        $event->addItem($slaves);

        $alerts = new MenuItemModel('alerts', "Alerts", "app_alert_index", [], 'fas fa-tachometer-alt');
        $event->addItem($alerts);

        $storage = new MenuItemModel('storage_nodes', "Storage nodes", "storagenode", [], 'fas fa-tachometer-alt');
        $event->addItem($storage);

        $admin = new MenuItemModel('admin', "Admin", "easyadmin", [], 'fas fa-tachometer-alt');
        $event->addItem($admin);

        return $event;
    }

    public function onSetupMenu(SidebarMenuEvent $event)
    {
        $event = $this->addStaticMenu($event);

        $domains = $this->domainRepository->findByParent(null);

        if (count($domains) > 0) {
            $title = new MenuItemModel('domains', "Root domains", false);
            $event->addItem($title);

            foreach ($domains as $domain) {
                $menuItem = new MenuItemModel('domain-' . $domain->getId(), $domain->getName(), 'app_domain_get', ['id' => $domain->getId()]);
                $event->addItem($menuItem);
            }
        }

        $this->activateByRoute(
            $event->getRequest()->get('_route'),
            (int)$event->getRequest()->get('id'),
            $event->getItems()
        );
    }

    public function onSetupBreadcrumbMenu(SidebarMenuEvent $event)
    {
        $event = $this->addStaticMenu($event);

        $domains = new MenuItemModel('domains', "Domains", false, [], 'fas fa-tachometer-alt');
        $event->addItem($domains);
        $this->addRecursiveDomains($domains);

        $this->activateByRoute(
            $event->getRequest()->get('_route'),
            (int)$event->getRequest()->get('id'),
            $event->getItems()
        );
    }

    private function addRecursiveDomains(MenuItemModel $parentMenuItem, Domain $parent = null)
    {
        $domains = $this->domainRepository->findByParent($parent);

        foreach ($domains as $domain) {
            $menuItem = new MenuItemModel('domain-' . $domain->getId(), $domain->getName(), 'app_domain_get', ['id' => $domain->getId()]);
            $parentMenuItem->addChild($menuItem);

            foreach ($domain->getDevices() as $device) {
                $deviceMenuItem = new MenuItemModel('device-' . $device->getId(), $device->getName(), 'app_device_get', ['id' => $device->getId()]);
                $menuItem->addChild($deviceMenuItem);
            }

            $parentMenuItem = $this->addRecursiveDomains($menuItem, $domain);
        }

        return $parentMenuItem;
    }

    /**
     * @param string $route
     * @param MenuItemModel[] $items
     */
    protected function activateByRoute($route, $id, $items)
    {
        foreach ($items as $item) {
            if ($route == "app_domain_get" || $route == "app_device_get") {
                if ($item->getRoute() == $route && $item->getRouteArgs()['id'] == $id) {
                    $item->setIsActive(true);
                }
            } else {
                if ($item->getRoute() == $route) {
                    $item->setIsActive(true);
                }
            }
            if ($item->hasChildren()) {
                $this->activateByRoute($route, $id, $item->getChildren());
            }
        }
    }
}