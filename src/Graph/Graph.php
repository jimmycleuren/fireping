<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace App\Graph;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;

abstract class Graph
{
    protected $storage;

    abstract function getSummaryGraph(Device $device, Probe $probe, $start = -43200, $end = null);

    abstract function getDetailGraph(Device $device, Probe $probe, SlaveGroup $slavegroup, $start = -3600, $end = null, $debug = false);
}