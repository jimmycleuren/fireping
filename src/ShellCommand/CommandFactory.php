<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */

namespace App\ShellCommand;

use Psr\Log\LoggerInterface;

class CommandFactory
{
    private $logger;

    protected static $mappings = array(
        'ping' => 'App\\ShellCommand\\PingShellCommand',
        'mtr' => 'App\\ShellCommand\\MtrShellCommand',
        'http' => 'App\\Probe\\Http',
        'traceroute' => 'App\\DependencyInjection\\Traceroute',
        'config-sync' => 'App\\ShellCommand\\GetConfigHttpWorkerCommand',
        'post-result' => 'App\\ShellCommand\\PostResultsHttpWorkerCommand',
    );

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create($command, $args)
    {
        if (!isset(self::$mappings[$command])) {
            throw new \Exception("No mapping exists for command $command.");
        }

        $class = self::$mappings[$command];
        return new $class($args, $this->logger);
    }
}