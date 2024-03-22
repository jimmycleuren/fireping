<?php

namespace App\Command;

use App\Services\CleanupService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class CleanupCommand.
 */
class CleanupCommand extends Command
{
    protected static $defaultName = 'app:cleanup';

    /**
     * CleanupCommand constructor.
     */
    public function __construct(private readonly LoggerInterface $logger, private readonly CleanupService $cleanupService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clean unused rrd-distributed files and folders');
    }

    /**
     * Executes the current command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('Cleanup');

        $this->cleanupService->cleanup();

        $event = $stopwatch->stop('Cleanup');

        $this->logger->info('Command took '.$event->getDuration().' ms');

        return 0;
    }
}
