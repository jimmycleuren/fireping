<?php
declare(strict_types=1);

namespace App\Model\Parameter\AlertDestination;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;
use Symfony\Component\Validator\Constraints as Assert;

class MailParameters extends DynamicParameters
{
    /**
     * @var string|null
     */
    protected $recipient;

    public function __construct(?string $recipient)
    {
        $this->recipient = $recipient;
    }

    public static function fromJsonString(string $json): DynamicParametersInterface
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    public function asArray(): array
    {
        return [
            'recipient' => $this->recipient,
        ];
    }

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self($in['recipient']);
    }
}