<?php

namespace App\EventSubscriber;

use App\Repository\AlertRepository;
use KevinPapst\AdminLTEBundle\Event\NotificationListEvent;
use KevinPapst\AdminLTEBundle\Helper\Constants;
use KevinPapst\AdminLTEBundle\Model\NotificationModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    private $alertRepository;

    public function __construct(AlertRepository $alertRepository)
    {
        $this->alertRepository = $alertRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NotificationListEvent::class => ['onNotifications', 100],
        ];
    }

    public function onNotifications(NotificationListEvent $event)
    {
        $alerts = $this->alertRepository->findBy(['active' => 1]);

        $counter = 0;
        foreach ($alerts as $alert) {
            $notification = new NotificationModel();
            $notification
                ->setId($alert->getId())
                ->setMessage($alert)
                ->setType(Constants::COLOR_YELLOW)
                ->setIcon('far fa-bell')
            ;
            $event->addNotification($notification);

            ++$counter;
            if ($counter >= 5) {
                break;
            }
        }

        $event->setTotal(count($alerts));
    }
}
