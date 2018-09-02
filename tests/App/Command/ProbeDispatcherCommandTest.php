<?php
declare(strict_types=1);

namespace Tests\App\Command;

use App\Command\ProbeDispatcherCommand;
use App\DependencyInjection\ProbeStore;
use App\Instruction\InstructionBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ProbeDispatcherCommandTest
 *
 * @package Tests\App\Command
 */
class ProbeDispatcherCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel      = self::bootKernel();
        $application = new Application($kernel);

        $probeStore = $this->prophesize(ProbeStore::class);
        $probeStore->getProbes()->willReturn([]);
        $probeStore->getEtag()->willReturn("etag");
        $probeStore         = $probeStore->reveal();

        $logger             = $this->prophesize(LoggerInterface::class)->reveal();
        $instructionBuilder = $this->prophesize(InstructionBuilder::class)->reveal();

        $application->add(
            new ProbeDispatcherCommand($probeStore, $logger, $instructionBuilder, $kernel)
        );

        $command       = $application->find('app:probe:dispatcher');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--env' => 'slave',
            '--max-runtime' => 20,
            '--workers' => 5
        ]);

        $output = $commandTester->getDisplay();
        static::assertContains('Max runtime reached', $output);
    }
}
