<?php

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;

class PingGraph extends SmokeRrdGraph
{
    protected $datasource = "ping";
}