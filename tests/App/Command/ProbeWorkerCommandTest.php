<?php

namespace Tests\App\Command;

use App\Command\ProbeWorkerCommand;
use App\Slave\Task\CommandFactory;
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

        $logger = self::$container->get(LoggerInterface::class);
        $factory = new CommandFactory($logger);

        $application->add(new ProbeWorkerCommand($logger, $factory));

        $command = $application->find('app:probe:worker');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--env' => 'slave',
            '--max-runtime' => 20,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Max runtime reached', $output);
    }
}
