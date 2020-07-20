<?php

declare(strict_types=1);

namespace App\Factory;

use App\Model\Parameter\DynamicParametersInterface;
use App\Model\Parameter\NullParameters;
use App\Model\Parameter\Probe\HttpParameters;
use App\Model\Parameter\Probe\PingParameters;
use App\Model\Parameter\Probe\TracerouteParameters;

class ProbeParameterFactory implements DynamicParameterFactoryInterface
{
    public function make(string $type, array $args): DynamicParametersInterface
    {
        switch ($type) {
            case 'http': return HttpParameters::fromArray($args);
            case 'ping': return PingParameters::fromArray($args);
            case 'traceroute': return TracerouteParameters::fromArray($args);
            default: return new NullParameters();
        }
    }
}