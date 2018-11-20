<?php

namespace App\Command;

use App\DependencyInjection\CleanupAlert;
use App\Services\CleanupService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;


/**
 * Class CleanupAlertCommand
 * @package App\Command
 */
class CleanupAlertCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:cleanupAlert';

    private $logger;
    private $cleanupAlert;

    /**
     * CleanupCommand constructor.
     * @param LoggerInterface $logger
     * @param CleanupAlert $cleanupAlert
     */
    public function __construct(LoggerInterface $logger, CleanupAlert $cleanupAlert)
    {
        $this->cleanupAlert = $cleanupAlert;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Clean alerts when a device is moved from slavegroup/alertrule');
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
        $stopwatch->start('CleanupAlert');

        $this->cleanupAlert->cleanup();

        $event = $stopwatch->stop('CleanupAlert');

        $output->writeln('Obsolete alerts are removed.');
        $this->logger->info('Command took ' . $event->getDuration() . ' ms');
    }
}
