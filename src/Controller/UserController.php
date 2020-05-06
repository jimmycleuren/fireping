<?php

namespace App\Controller;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserController extends EasyAdminController
{

    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function hashPassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword();

        if ($plainPassword === '') {
            return;
        }

        $encoder = $this->encoderFactory->getEncoder($user);

        $hashedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }

    public function persistUserEntity(User $user): void
    {
        $user->setEnabled(true);
        $this->hashPassword($user);
        $this->updateEntity($user);
    }

    public function updateUserEntity(User $user): void
    {
        $this->hashPassword($user);
        $this->updateEntity($user);
    }

}