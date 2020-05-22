<?php

namespace App\EventSubscriber;

use App\Entity\User;
use KevinPapst\AdminLTEBundle\Event\ShowUserEvent;
use KevinPapst\AdminLTEBundle\Event\NavbarUserEvent;
use KevinPapst\AdminLTEBundle\Event\SidebarUserEvent;
use KevinPapst\AdminLTEBundle\Model\NavBarUserLink;
use KevinPapst\AdminLTEBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class NavbarUserSubscriber implements EventSubscriberInterface
{
    protected $security;
    protected $session;

    public function __construct(Security $security, SessionInterface $session)
    {
        $this->security = $security;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NavbarUserEvent::class => ['onShowUser', 100],
            //SidebarUserEvent::class => ['onShowUser', 100],
        ];
    }

    public function onShowUser(ShowUserEvent $event)
    {
        if (null === $this->security->getUser()) {
            return;
        }

        /* @var $myUser User */
        $myUser = $this->security->getUser();

        $event->setShowProfileLink(false);
        if ($this->session->get('debug') === true) {
            $event->addLink(new NavBarUserLink("Hide graph trends", "debug"));
        } else {
            $event->addLink(new NavBarUserLink("Show graph trends", "debug"));
        }

        $user = new UserModel();
        $user
            ->setId($myUser->getId())
            ->setName($myUser->getUsername())
            ->setUsername($myUser->getUsername())
            ->setIsOnline(true)
            ->setTitle($myUser->getRoles()[0])
            //->setAvatar($myUser->getAvatar())
            //->setMemberSince($myUser->getRegisteredAt())
        ;

        $event->setUser($user);
    }
}