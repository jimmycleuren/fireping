<?php

namespace App\EventSubscriber;

use App\Entity\Alert;
use App\Repository\AlertRepository;
use KevinPapst\AdminLTEBundle\Event\NotificationListEvent;
use KevinPapst\AdminLTEBundle\Helper\Constants;
use KevinPapst\AdminLTEBundle\Model\NotificationModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AlertRepository $alertRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NotificationListEvent::class => ['onNotifications', 100],
        ];
    }

    public function onNotifications(NotificationListEvent $event): void
    {
        /**
         * @var Alert[] $alerts
         */
        $alerts = $this->alertRepository->findBy(['active' => 1]);

        $counter = 0;
        foreach ($alerts as $alert) {
            $notification = new NotificationModel();
            $notification
                ->setId((string) $alert->getId())
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
