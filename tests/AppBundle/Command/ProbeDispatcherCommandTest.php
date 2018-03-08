<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\ProbeDispatcherCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProbeDispatcherCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new ProbeDispatcherCommand());

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