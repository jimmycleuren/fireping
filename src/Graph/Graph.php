<?php

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

abstract class Graph
{
    protected $storage;

    abstract public function getSummaryGraph(Device $device, Probe $probe, $start = -43200, $end = null, $width = 600);

    abstract public function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $debug = false);
}
