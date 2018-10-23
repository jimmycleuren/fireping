<?php

namespace App\Command;

use App\Services\CleanupService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class CleanupCommand
 * @package App\Command
 */
class CleanupCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:cleanup';

    private $logger;
    private $cleanupService;

    /**
     * CleanupCommand constructor.
     * @param LoggerInterface $logger
     * @param CleanupService $cleanupService
     */
    public function __construct(LoggerInterface $logger, CleanupService $cleanupService)
    {
        $this->cleanupService = $cleanupService;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Clean unused rrd-distributed files and folders');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('Cleanup');

        $this->cleanupService->cleanup();

        $event = $stopwatch->stop('Cleanup');

        echo 'Command took ' . $event->getDuration() . ' ms' . PHP_EOL;
        $this->logger->info('Command took ' . $event->getDuration() . ' ms');
    }
}
