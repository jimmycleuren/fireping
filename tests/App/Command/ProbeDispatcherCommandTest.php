<?php

namespace Tests\App\Command;

use App\Command\ProbeDispatcherCommand;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProbeDispatcherCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $logger = $this->prophesize("Psr\Log\LoggerInterface");
        $application->add(new ProbeDispatcherCommand($logger->reveal()));

        $command = $application->find('app:probe:dispatcher');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--env' => 'slave',
            '--max-runtime' => 20,
            '--workers' => 5,
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains("Max runtime reached", $output);
    }
}