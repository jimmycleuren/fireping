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
}