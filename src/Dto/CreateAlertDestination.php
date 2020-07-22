<?php
declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Core\Annotation\ApiProperty;
use App\Entity\AlertDestination;
use App\Model\Parameter\DynamicParametersInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateAlertDestination
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    public $name;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getTypes")
     */
    public $type;

    /**
     * @var array|DynamicParametersInterface
     * @Assert\Valid()
     */
    public $parameters = [];

    public static function getTypes(): array
    {
        return AlertDestination::ALLOWED_TYPES;
    }
}