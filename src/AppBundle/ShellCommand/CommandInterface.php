<?php

namespace AppBundle\ShellCommand;

/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */
interface CommandInterface
{
    function __construct($args);
    function execute();
    function build();
    function valid();
}