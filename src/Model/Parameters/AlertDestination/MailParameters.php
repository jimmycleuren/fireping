<?php
declare(strict_types=1);

namespace App\Model\Parameters\AlertDestination;

use App\Model\Parameters\JsonParameters;
use App\Model\Parameters\JsonParametersInterface;

class MailParameters extends JsonParameters
{
    /**
     * @var string|null
     */
    private $recipient;

    private function __construct(?string $recipient)
    {
        $this->recipient = $recipient;
    }

    public static function fromJsonString(string $json): JsonParametersInterface
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

    public static function fromArray(array $in): JsonParametersInterface
    {
        return new self($in['recipient']);
    }
}
