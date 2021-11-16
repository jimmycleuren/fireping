<?php

namespace App\Tests\Slave\Command;

use App\Slave\Command\ProbeWorkerCommand;
use App\Slave\Task\TaskFactory;
use Psr\Log\Test\TestLogger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProbeWorkerCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $logger = new TestLogger();
        $factory = new TaskFactory($logger);

        $application->add(new ProbeWorkerCommand($logger, $factory));

        $command = $application->find('app:probe:worker');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--env' => 'slave',
            '--max-runtime' => 5,
        ]);

        $this->assertTrue($logger->hasInfoThatContains('max runtime reached'));
    }
}
