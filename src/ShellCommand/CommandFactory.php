<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */

namespace App\ShellCommand;

class CommandFactory
{
    protected static $mappings = array(
        'ping' => 'App\\ShellCommand\\PingShellCommand',
        'mtr' => 'App\\ShellCommand\\MtrShellCommand',
        'traceroute' => 'App\\DependencyInjection\\Traceroute',
        'config-sync' => 'App\\ShellCommand\\GetConfigHttpWorkerCommand',
        'post-result' => 'App\\ShellCommand\\PostResultsHttpWorkerCommand',
    );

    public function create($command, $args)
    {
        if (!isset(self::$mappings[$command])) {
            throw new \Exception("No mapping exists for command $command.");
        }

        $class = self::$mappings[$command];
        return new $class($args);
    }
}