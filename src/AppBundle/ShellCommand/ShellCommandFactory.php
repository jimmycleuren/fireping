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
    );

    public function create($command, $args)
    {
        $class = self::$mappings[$command];
        return new $class($command, $args);
    }
}