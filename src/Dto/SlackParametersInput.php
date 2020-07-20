<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SlackParametersInput
{
    /**
     * @var string|null
     * @Assert\NotBlank()
     */
    public $channel;

    /**
     * @var string|null
     * @Assert\Url()
     * @Assert\NotBlank()
     */
    public $url;
}