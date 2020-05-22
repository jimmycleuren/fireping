<?php

namespace App\EventSubscriber;

use KevinPapst\AdminLTEBundle\Event\NotificationListEvent;
use KevinPapst\AdminLTEBundle\Helper\Constants;
use KevinPapst\AdminLTEBundle\Model\NotificationModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            NotificationListEvent::class => ['onNotifications', 100],
        ];
    }

    public function onNotifications(NotificationListEvent $event)
    {
        $notification = new NotificationModel();
        $notification
            ->setId(1)
            ->setMessage('A demo message')
            ->setType(Constants::COLOR_YELLOW)
            ->setIcon('far fa-bell')
        ;
        $event->addNotification($notification);

        /*
         * You can also set the total number of notifications which could be different from those displayed in the navbar
         * If no total is set, the total will be calculated on the number of notifications added to the event
         */
        $event->setTotal(15);
    }
}