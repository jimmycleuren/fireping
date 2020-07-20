<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class PatchAlertDestination
{
    /**
     * @var string|null
     * @Assert\NotBlank(allowNull=true)
     */
    public $name;
    /**
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     */
    public $parameters;
}