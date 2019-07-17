<?php
declare(strict_types=1);

namespace App\ShellCommand;

interface CommandInterface
{
    function execute();
    function build();
    function valid();
}