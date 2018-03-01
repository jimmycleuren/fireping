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
use AppBundle\Exception\RrdException;
use AppBundle\Storage\RrdStorage;

abstract class RrdGraph extends Graph
{
    public function __construct(RrdStorage $rrdStorage)
    {
        $this->storage = $rrdStorage;
    }
}