<?php

declare(strict_types=1);

namespace App\Model\Parameters\AlertDestination;

use App\Model\Parameters\JsonParameters;
use App\Model\Parameters\JsonParametersInterface;

class HttpParameters extends JsonParameters
{
    /**
     * @var string|null
     */
    protected $url;

    private function __construct(?string $url)
    {
        $this->url = $url;
    }

    public static function fromJsonString(string $json): JsonParametersInterface
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    public function asArray(): array
    {
        return [
            'url' => $this->url,
        ];
    }

    public static function fromArray(array $in): JsonParametersInterface
    {
        return new self($in['url'] ?? null);
    }
}
