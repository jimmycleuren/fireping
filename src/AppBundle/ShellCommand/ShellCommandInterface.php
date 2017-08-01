<?php

namespace AppBundle\ShellCommand;

/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */
interface ShellCommandInterface
{
    function execute();
    function build();
    function valid();
}