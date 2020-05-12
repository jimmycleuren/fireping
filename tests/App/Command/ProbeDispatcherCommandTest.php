<?php
declare(strict_types=1);

namespace Tests\App\Command;

use App\Command\ProbeDispatcherCommand;
use App\DependencyInjection\SlaveConfiguration;
use App\DependencyInjection\StatsManager;
use App\DependencyInjection\Worker;
use App\DependencyInjection\WorkerManager;
use Prophecy\Argument;
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

        $logger = self::$container->get(LoggerInterface::class);

        $worker = $this->prophesize(Worker::class);
        $worker->send(Argument::any(), Argument::type('int'), Argument::any())->willReturn(true);
        $worker->__toString()->willReturn("worker");
        $worker = $worker->reveal();

        $workerManager = $this->prophesize(WorkerManager::class);
        $workerManager->initialize(Argument::type('int'), Argument::type('int'), Argument::type('int'))->shouldBeCalledTimes(1);
        $workerManager->loop()->willReturn();
        $workerManager->getWorker(Argument::any())->willReturn($worker);
        $workerManager->getTotalWorkers()->willReturn(10);
        $workerManager->getAvailableWorkers()->willReturn(5);
        $workerManager->getInUseWorkerTypes()->willReturn(['ping' => 2, 'traceroute' => 3]);

        $statsManager = $this->prophesize(StatsManager::class);

        $application->add(new ProbeDispatcherCommand($logger, $workerManager->reveal(), $statsManager->reveal()));

        $command       = $application->find('app:probe:dispatcher');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--env' => 'slave',
            '--max-runtime' => 20,
            '--workers' => 5
        ]);

        $output = $commandTester->getDisplay();
        static::assertStringContainsString('Max runtime reached', $output);
    }
}
