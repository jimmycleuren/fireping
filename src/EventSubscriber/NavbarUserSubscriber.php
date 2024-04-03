<?php

namespace App\EventSubscriber;

use App\Entity\User;
use KevinPapst\AdminLTEBundle\Event\NavbarUserEvent;
use KevinPapst\AdminLTEBundle\Event\ShowUserEvent;
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

    public function onShowUser(ShowUserEvent $event): void
    {
        $event->setShowProfileLink(false);

        if (null === $this->security->getUser()) {
            $user = new UserModel();
            $user
                ->setId('guest')
                ->setName('Guest')
                //->setUsername($myUser->getUsername())
                ->setIsOnline(true)
                ->setTitle('Guest')
                //->setAvatar($myUser->getAvatar())
            ;

            $event->setUser($user);
            $event->setShowLogoutLink(false);
            $event->addLink(new NavBarUserLink('Login', 'app_login'));
        } else {
            /* @var $myUser User */
            $myUser = $this->security->getUser();

            if (true === $this->session->get('debug')) {
                $event->addLink(new NavBarUserLink('Hide graph trends', 'debug'));
            } else {
                $event->addLink(new NavBarUserLink('Show graph trends', 'debug'));
            }

            $user = new UserModel();
            $user
                ->setId($myUser->getId())
                ->setName($myUser->getUsername())
                ->setUsername($myUser->getUsername())
                ->setIsOnline(true)
                ->setTitle($myUser->getRoles()[0])//->setAvatar($myUser->getAvatar())
            ;

            $event->setUser($user);
        }
    }
}
