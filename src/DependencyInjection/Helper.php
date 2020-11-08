<?php

namespace App\DependencyInjection;

class Helper
{
    public function getProbeGraphTypes(string $probeType)
    {
        switch($probeType) {
            case 'http': return ['latency', 'response'];
            default: return ['default'];
        }
    }

    public function getColor($id, $total)
    {
        $width = 127;
        $center = 128;
        $frequency = pi() * 2 / $total;

        $red = sin($frequency * $id + 0) * $width + $center;
        $green = sin($frequency * $id + 2) * $width + $center;
        $blue = sin($frequency * $id + 4) * $width + $center;

        return sprintf('%02x', $red).sprintf('%02x', $green).sprintf('%02x', $blue);
    }
}