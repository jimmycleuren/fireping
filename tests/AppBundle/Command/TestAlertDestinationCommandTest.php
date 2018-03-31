<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\TestAlertDestinationCommand;
use AppBundle\Command\ValidateRrdCommand;
use AppBundle\Entity\AlertDestination;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TestAlertDestinationCommandTest extends KernelTestCase
{
    public function testExecuteInvalid()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $destination = $this->prophesize("AppBundle\AlertDestination\Monolog");
        $factory = $this->prophesize("AppBundle\AlertDestination\AlertDestinationFactory");
        $factory->create(Argument::any())->willReturn($destination->reveal());
        $logger = $this->prophesize("Psr\Log\LoggerInterface");
        $logger->warning(Argument::type('string'))->shouldBeCalledTimes(1);

        $application->add(new TestAlertDestinationCommand($kernel->getContainer()->get('doctrine')->getManager(), $factory->reveal(), $logger->reveal()));

        $command = $application->find('app:alert:test');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'destination-id' => 9999
        ));

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $destination = $this->prophesize("AppBundle\AlertDestination\Monolog");
        $factory = $this->prophesize("AppBundle\AlertDestination\AlertDestinationFactory");
        $factory->create(Argument::any())->willReturn($destination->reveal());
        $logger = $this->prophesize("Psr\Log\LoggerInterface");

        $application->add(new TestAlertDestinationCommand($kernel->getContainer()->get('doctrine')->getManager(), $factory->reveal(), $logger->reveal()));

        $command = $application->find('app:alert:test');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'destination-id' => 1
        ));

        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}