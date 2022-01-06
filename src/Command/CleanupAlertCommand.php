<?php

declare(strict_types=1);

namespace App\Command;

use App\DependencyInjection\CleanupAlert;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CleanupAlertCommand extends Command
{
    protected static $defaultName = 'app:cleanupAlert';

    private $logger;
    private $cleanupAlert;

    public function __construct(LoggerInterface $logger, CleanupAlert $cleanupAlert)
    {
        $this->cleanupAlert = $cleanupAlert;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Clean alerts when a device is moved from slavegroup/alertrule');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('CleanupAlert');
        $this->cleanupAlert->cleanup();
        $event = $stopwatch->stop('CleanupAlert');
        $output->writeln(sprintf('Removed obsolete alerts in %sms', $event->getDuration()));

        return 0;
    }
}
