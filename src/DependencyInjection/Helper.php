<?php

namespace App\DependencyInjection;

class Helper
{
    public function getProbeGraphTypes(string $probeType)
    {
        return match ($probeType) {
            'http' => ['latency', 'response'],
            default => ['default'],
        };
    }
}