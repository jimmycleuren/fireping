<?php

namespace App\ShellCommand;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */
interface CommandInterface
{
    function __construct($args, LoggerInterface $logger, ContainerInterface $container);
    function execute();
    function build();
    function valid();
}