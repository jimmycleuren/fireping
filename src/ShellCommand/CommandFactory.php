<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */

namespace App\ShellCommand;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CommandFactory
{
    private $logger;
    private $container;

    protected static $mappings = array(
        'ping' => 'App\\ShellCommand\\PingShellCommand',
        'http' => 'App\\Probe\\Http',
        'traceroute' => 'App\\DependencyInjection\\Traceroute',
        'config-sync' => 'App\\ShellCommand\\GetConfigHttpWorkerCommand',
        'post-result' => 'App\\ShellCommand\\PostResultsHttpWorkerCommand',
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