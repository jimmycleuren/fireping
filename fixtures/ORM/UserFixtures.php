<?php

namespace App\DataFixtures\ORM;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserFixtures extends Fixture
{
    private $encoder;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoder = $encoderFactory;
    }

    public function load(ObjectManager $manager)
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
        $encoder = $this->encoder->getEncoder($user);
        $hashedPassword = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($hashedPassword);
        $manager->persist($user);
        $manager->flush();

        $slave = new User();
        $slave->setUsername('slave01');
        $slave->setEmail('slave01@fireping.be');
        $slave->setPassword($encoder->encodePassword('test123', $slave->getSalt()));
        $slave->setEnabled(true);
        $slave->setRoles(['ROLE_API']);
        $manager->persist($slave);
        $manager->flush();
    }
}
