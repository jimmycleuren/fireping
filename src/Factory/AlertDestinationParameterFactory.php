<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\AlertDestination;
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
            case AlertDestination::TYPE_HTTP: return HttpParameters::fromArray($args);
            case AlertDestination::TYPE_MAIL: return MailParameters::fromArray($args);
            case AlertDestination::TYPE_LOG: return MonologParameters::fromArray($args);
            case AlertDestination::TYPE_SLACK: return SlackParameters::fromArray($args);
            default: return new NullParameters();
        }
    }
}