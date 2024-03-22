<?php

namespace App\DataFixtures\ORM;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Create our user and set details
        $user = new User();
        $user->setUsername('test');
        $user->setEmail('test@fireping.be');
        $user->setPlainPassword('test123');

        //$user->setPassword('3NCRYPT3D-V3R51ON');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_ADMIN']);

        // Update the user
        $encoder = $this->passwordHasherFactory->getPasswordHasher($user);
        $hashedPassword = $encoder->hash($user->getPlainPassword());
        $user->setPassword($hashedPassword);
        $manager->persist($user);
        $manager->flush();

        $slave = new User();
        $slave->setUsername('slave01');
        $slave->setEmail('slave01@fireping.be');
        $slave->setPassword($encoder->hash('test123'));
        $slave->setEnabled(true);
        $slave->setRoles(['ROLE_API']);
        $manager->persist($slave);
        $manager->flush();
    }
}
