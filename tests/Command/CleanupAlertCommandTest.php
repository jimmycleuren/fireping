<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CleanupAlertCommand;
use App\DependencyInjection\CleanupAlert;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanupAlertCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $service = $this->prophesize(CleanupAlert::class);

        $application->add(new CleanupAlertCommand($service->reveal()));

        $command = $application->find('app:cleanupAlert');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Removed obsolete alerts', $output);
    }
}
