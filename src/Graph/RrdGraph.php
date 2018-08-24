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
use App\Storage\StorageFactory;

abstract class RrdGraph extends Graph
{
    public function __construct(StorageFactory $storageFactory)
    {
        $this->storage = $storageFactory->create();
    }
}