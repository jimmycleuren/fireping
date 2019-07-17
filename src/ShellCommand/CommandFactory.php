<?php
declare(strict_types=1);

namespace App\ShellCommand;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CommandFactory
{
    private $logger;
    private $container;

    protected static $mappings = array(
        'ping' => PingShellCommand::class,
        'http' => Http::class,
        'traceroute' => Traceroute::class,
        'config-sync' => GetConfigHttpWorkerCommand::class,
        'post-result' => PostResultsHttpWorkerCommand::class,
    );

    public function __construct(LoggerInterface $logger, ContainerInterface $container)
    {
        $this->logger = $logger;
        $this->container = $container;
    }

    public function create($command, $args)
    {
        if (!isset(self::$mappings[$command])) {
            throw new \Exception("No mapping exists for command $command.");
        }

        $class = self::$mappings[$command];
        return new $class($args, $this->logger, $this->container);
    }
}