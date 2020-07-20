<?php

declare(strict_types=1);

namespace App\Dto;

final class AlertDestinationOutput
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $parameters = [];
}