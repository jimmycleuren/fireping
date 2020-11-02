<?php

namespace App\EventSubscriber;

use App\Entity\Domain;
use App\Repository\AlertRepository;
use App\Repository\DomainRepository;
use App\Repository\SlaveRepository;
use App\Repository\StorageNodeRepository;
use KevinPapst\AdminLTEBundle\Event\BreadcrumbMenuEvent;
use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;

class MenuBuilderSubscriber implements EventSubscriberInterface
{
    private $alertRepository;
    private $domainRepository;
    private $slaveRepository;
    private $storageNodeRepository;
    private $security;

    public function __construct(DomainRepository $domainRepository, Security $security, AlertRepository $alertRepository, SlaveRepository $slaveRepository, StorageNodeRepository $storageNodeRepository)
    {
        $this->alertRepository = $alertRepository;
        $this->domainRepository = $domainRepository;
        $this->slaveRepository = $slaveRepository;
        $this->storageNodeRepository = $storageNodeRepository;
        $this->security = $security;
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
        $slaves = new MenuItemModel('slaves', 'Slaves', 'slave_index', [], 'fas fa-microchip');
        $count = count($this->slaveRepository->findAll());
        $color = 'green';
        foreach ($this->slaveRepository->findAll() as $slave) {
            if ($slave->getLastContact() < new \DateTime('5 minutes ago')) {
                $color = 'red';
            }
        }
        $slaves->setBadge($count);
        $slaves->setBadgeColor($color);
        $event->addItem($slaves);

        $alerts = new MenuItemModel('alerts', 'Alerts', 'app_alert_index', [], 'far fa-bell');
        $count = count($this->alertRepository->findBy(['active' => 1]));
        $alerts->setBadge($count);
        if ($count > 0) {
            $alerts->setBadgeColor('yellow');
        } else {
            $alerts->setBadgeColor('green');
        }
        $event->addItem($alerts);

        $storage = new MenuItemModel('storage_nodes', 'Storage nodes', 'storagenode', [], 'far fa-hdd');
        $count = count($this->storageNodeRepository->findAll());
        $storage->setBadge($count);
        $storage->setBadgeColor('blue');
        $event->addItem($storage);

        return $event;
    }

    public function onSetupMenu(SidebarMenuEvent $event)
    {
        $event = $this->addStaticMenu($event);

        $domains = $this->domainRepository->findBy(['parent' => null]);

        if (count($domains) > 0) {
            $title = new MenuItemModel('domains', 'Root domains', '');
            $event->addItem($title);

            foreach ($domains as $domain) {
                $menuItem = new MenuItemModel('domain-'.$domain->getId(), $domain->getName(), 'app_domain_get', ['id' => $domain->getId()], 'fas fa-layer-group');
                $event->addItem($menuItem);
            }
        }

        $this->activateByRoute(
            $event->getRequest()->get('_route'),
            (int) $event->getRequest()->get('id'),
            $event->getItems()
        );
    }

    public function onSetupBreadcrumbMenu(SidebarMenuEvent $event)
    {
        $event = $this->addStaticMenu($event);

        $domains = new MenuItemModel('domains', 'Domains', '', [], '');
        $event->addItem($domains);
        $this->addRecursiveDomains($domains);

        $this->activateByRoute(
            $event->getRequest()->get('_route'),
            (int) $event->getRequest()->get('id'),
            $event->getItems()
        );
    }

    private function addRecursiveDomains(MenuItemModel $parentMenuItem, Domain $parent = null)
    {
        $domains = $this->domainRepository->findBy(['parent' => $parent]);

        foreach ($domains as $domain) {
            $menuItem = new MenuItemModel('domain-'.$domain->getId(), $domain->getName(), 'app_domain_get', ['id' => $domain->getId()]);
            $parentMenuItem->addChild($menuItem);

            foreach ($domain->getDevices() as $device) {
                $deviceMenuItem = new MenuItemModel('device-'.$device->getId(), $device->getName(), 'app_device_get', ['id' => $device->getId()]);
                $menuItem->addChild($deviceMenuItem);
            }

            $this->addRecursiveDomains($menuItem, $domain);
        }

        return $parentMenuItem;
    }

    /**
     * @param string          $route
     * @param MenuItemModel[] $items
     */
    protected function activateByRoute($route, $id, $items)
    {
        foreach ($items as $item) {
            if ('app_domain_get' == $route || 'app_device_get' == $route) {
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
