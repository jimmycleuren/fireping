<?php

namespace App\Tests\App\Command;

use App\AlertDestination\AlertDestinationFactory;
use App\AlertDestination\Monolog;
use App\Command\TestAlertDestinationCommand;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TestAlertDestinationCommandTest extends KernelTestCase
{
    use ProphecyTrait;

    public function testExecuteInvalid()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $destination = $this->prophesize(Monolog::class);
        $factory = $this->prophesize(AlertDestinationFactory::class);
        $factory->create(Argument::any())->willReturn($destination->reveal());
        $logger = $this->prophesize(LoggerInterface::class);
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

    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $destination = $this->prophesize(Monolog::class);
        $factory = $this->prophesize(AlertDestinationFactory::class);
        $factory->create(Argument::any())->willReturn($destination->reveal());
        $logger = $this->prophesize(LoggerInterface::class);

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
