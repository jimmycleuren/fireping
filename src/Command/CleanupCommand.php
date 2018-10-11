<?php

namespace App\Command;

use App\Services\CleanupService;
use App\Storage\StorageFactory;
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
    private $storage;
    private $cleanupService;

    /**
     * CleanupCommand constructor.
     * @param LoggerInterface $logger
     * @param StorageFactory $storageFactory
     * @param CleanupService $cleanupService
     */
    public function __construct(LoggerInterface $logger, StorageFactory $storageFactory, CleanupService $cleanupService)
    {
        $this->cleanupService = $cleanupService;
        $this->storage = $storageFactory;
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

        $this->storage->create()->cleanup($this->cleanupService);

        $event = $stopwatch->stop('Cleanup');

        echo 'Command took ' . $event->getDuration() . ' ms' . PHP_EOL;
        $this->logger->info('Command took ' . $event->getDuration() . ' ms');
    }
}
