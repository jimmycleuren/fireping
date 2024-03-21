<?php

namespace App\Tests\Command;

use App\Command\TestAlertDestinationCommand;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TestAlertDestinationCommandTest extends KernelTestCase
{
    public function testExecuteInvalid(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $destination = $this->prophesize(\App\AlertDestination\Monolog::class);
        $factory = $this->prophesize(\App\AlertDestination\AlertDestinationFactory::class);
        $factory->create(Argument::any())->willReturn($destination->reveal());
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);
        $logger->warning(Argument::type('string'))->shouldBeCalledTimes(1);

        $application->add(new TestAlertDestinationCommand($kernel->getContainer()->get('doctrine')->getManager(), $factory->reveal(), $logger->reveal()));

        $command = $application->find('app:alert:test');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'destination-id' => 9999,
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $destination = $this->prophesize(\App\AlertDestination\Monolog::class);
        $factory = $this->prophesize(\App\AlertDestination\AlertDestinationFactory::class);
        $factory->create(Argument::any())->willReturn($destination->reveal());
        $logger = $this->prophesize(\Psr\Log\LoggerInterface::class);

        $application->add(new TestAlertDestinationCommand($kernel->getContainer()->get('doctrine')->getManager(), $factory->reveal(), $logger->reveal()));

        $command = $application->find('app:alert:test');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'destination-id' => 1,
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}
