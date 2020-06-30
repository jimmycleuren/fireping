<?php

namespace App\Graph;

use App\Entity\Device;
use App\Storage\StorageFactory;

abstract class RrdGraph extends Graph
{
    protected $colors = [
        [
            'main' => '#0000ff',
            'stddev' => '#0000ff44',
        ],
        [
            'main' => '#00ff00',
            'stddev' => '#00ff0044',
        ],
        [
            'main' => '#ff0000',
            'stddev' => '#ff000044',
        ],
    ];

    public function __construct(StorageFactory $storageFactory)
    {
        $this->storage = $storageFactory->create();
    }

    protected function getMedianMax(Device $device, $start, $end, $file)
    {
        $options = [
            '--start', $start,
            '--end', $end,
            '--width=600',
            "DEF:max=$file:median:AVERAGE",
            'PRINT:max:MAX:%le',
        ];

        $maxMedian = $this->storage->getGraphValue($device, $options);

        return $maxMedian * 1.2;
    }
}
