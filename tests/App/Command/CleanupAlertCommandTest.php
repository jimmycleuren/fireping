<?php

namespace Tests\App\Command;

use App\Command\CleanupAlertCommand;
use App\Command\CleanupCommand;
use App\DependencyInjection\CleanupAlert;
use App\Services\CleanupService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CleanupCommandTest
 * @package Tests\App\Command
 */
class CleanupAlertCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $logger = $this->prophesize("Psr\Log\LoggerInterface");
        $factory = $this->prophesize(CleanupAlert::class);

        $application->add(new CleanupAlertCommand($logger->reveal(), $factory->reveal()));

        $command = $application->find('app:cleanupAlert');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName()));

        $output = $commandTester->getDisplay();
        $this->assertContains("Obsolete alerts", $output);
    }
}
