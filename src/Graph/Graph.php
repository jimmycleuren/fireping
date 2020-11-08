<?php

namespace App\Graph;

use App\DependencyInjection\Helper;
use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

abstract class Graph
{
    protected $storage;

    abstract public function getSummaryGraph(Device $device, Probe $probe, Helper $helper, $start = -43200, $end = null, $width = 600);

    abstract function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, Helper $helper, $start = -3600, $end = null, $type = "default", $debug = false);
}
