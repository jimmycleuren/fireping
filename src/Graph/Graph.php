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

abstract class Graph
{
    protected $storage;

    abstract function getSummaryGraph(Device $device, Probe $probe);
}