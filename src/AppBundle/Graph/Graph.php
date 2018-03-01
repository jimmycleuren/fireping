<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace AppBundle\Graph;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;

abstract class Graph
{
    protected $storage;

    abstract function getSummaryGraph(Device $device, Probe $probe);
}