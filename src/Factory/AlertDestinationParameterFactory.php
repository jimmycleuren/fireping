<?php

declare(strict_types=1);

namespace App\Factory;

use App\Model\Parameter\AlertDestination\HttpParameters;
use App\Model\Parameter\AlertDestination\MailParameters;
use App\Model\Parameter\AlertDestination\MonologParameters;
use App\Model\Parameter\AlertDestination\SlackParameters;
use App\Model\Parameter\DynamicParametersInterface;
use App\Model\Parameter\NullParameters;

class AlertDestinationParameterFactory implements DynamicParameterFactoryInterface
{
    public function make(string $type, array $args): DynamicParametersInterface
    {
        switch ($type) {
            case 'http': return HttpParameters::fromArray($args);
            case 'mail': return MailParameters::fromArray($args);
            case 'monolog': return MonologParameters::fromArray($args);
            case 'slack': return SlackParameters::fromArray($args);
            default: return new NullParameters();
        }
    }
}