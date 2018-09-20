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
    protected $colors = array(
        array(
            'main' => '#0000ff',
            'stddev' => '#0000ff44'
        ),
        array(
            'main' => '#00ff00',
            'stddev' => '#00ff0044'
        ),
        array(
            'main' => '#ff0000',
            'stddev' => '#ff000044'
        ),
    );

    public function __construct(StorageFactory $storageFactory)
    {
        $this->storage = $storageFactory->create();
    }

    protected function getMedianMax(Device $device, $start, $end, $file)
    {
        $options = array(
            "--start", $start,
            "--end", $end,
            "--width=600",
            "DEF:max=$file:median:AVERAGE",
            "PRINT:max:MAX:%le"
        );

        $maxMedian = $this->storage->getGraphValue($device, $options);

        return $maxMedian * 1.2;
    }
}