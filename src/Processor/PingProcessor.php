<?php

namespace App\Processor;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\WrongTimestampRrdException;

class PingProcessor extends SmokeProcessor
{
    protected $datasource = "ping";
}