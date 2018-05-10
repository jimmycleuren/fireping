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
use App\Exception\RrdException;
use App\Storage\RrdStorage;

abstract class RrdGraph extends Graph
{
    public function __construct(RrdStorage $rrdStorage)
    {
        $this->storage = $rrdStorage;
    }
}