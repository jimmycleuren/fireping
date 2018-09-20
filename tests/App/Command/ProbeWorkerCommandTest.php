<?php

namespace Tests\App\Command;

use App\Command\ProbeWorkerCommand;
use App\ShellCommand\CommandFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProbeWorkerCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $logger = $this->prophesize("Psr\Log\LoggerInterface");
        $factory = $this->prophesize(CommandFactory::class);

        $application->add(new ProbeWorkerCommand($logger->reveal(), $factory->reveal()));

        $command = $application->find('app:probe:worker');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--env' => 'slave',
            '--max-runtime' => 20
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains("Max runtime reached", $output);
    }
}