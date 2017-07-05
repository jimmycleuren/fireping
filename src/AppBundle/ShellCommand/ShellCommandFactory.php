<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */

namespace AppBundle\ShellCommand;

use AppBundle\ShellCommand\PingShellCommand;
use AppBundle\ShellCommand\MtrShellCommand;


class ShellCommandFactory
{
    protected static $mappings = array(
        'ping' => 'AppBundle\\ShellCommand\\PingShellCommand',
        'mtr' => 'AppBundle\\ShellCommand\\MtrShellCommand',
        'traceroute' => 'AppBundle\\ShellCommand\\TracerouteShellCommand',
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