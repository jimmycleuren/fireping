<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EasyAdminPasswordUpdaterSubscriber implements EventSubscriberInterface
{
    private UserPasswordHasherInterface $passwordEncoder;
    private LoggerInterface $logger;

    public function __construct(UserPasswordHasherInterface $passwordEncoder, LoggerInterface $logger)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->logger = $logger;
    }

    /**
     * @param BeforeEntityPersistedEvent|BeforeEntityUpdatedEvent $event
     */
    public function encodePassword($event)
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof User === false) {
            return;
        }

        if ($entity->getPlainPassword()) {
            $entity->setPassword($this->passwordEncoder->hashPassword($entity, $entity->getPlainPassword()));
            $entity->eraseCredentials();
            $this->logger->error('Password updated.', ['user.id' => $entity->getId()]);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityUpdatedEvent::class => ['encodePassword'],
            BeforeEntityPersistedEvent::class => ['encodePassword']
        ];
    }
}
