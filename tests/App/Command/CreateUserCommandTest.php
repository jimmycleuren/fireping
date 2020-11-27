<?php

namespace App\Tests\App\Command;

use App\Command\CleanupCommand;
use App\Command\CreateUserCommand;
use App\Entity\User;
use App\Services\CleanupService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class CreateUserCommandTest
 */
class CreateUserCommandTest extends KernelTestCase
{
    private $em;
    private $application;

    public function setUp() : void
    {
        $kernel = self::bootKernel();
        $container = self::$container;

        $this->application = new Application($kernel);

        $this->em = $container->get('doctrine')->getManager();
    }

    public function testExecute()
    {
        $cleanUpCommand = new CreateUserCommand(
            $this->em,
            new UserPasswordEncoder(new EncoderFactory([User::class => ['algorithm' => 'bcrypt']]))
        );

        $this->application->add($cleanUpCommand);

        $command = $this->application->find('fireping:create:user');
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['tester', 'pass', 'a@b.c', '1']);

        $commandTester->execute(array(
            'command'  => $command->getName(),
        ));

        $user = $this->em->getRepository(User::class)->findOneByUsername('tester');

        $this->assertNotNull($user);
    }
}
