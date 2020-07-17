<?php

declare(strict_types=1);

namespace App\Model\Parameter\AlertDestination;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;

class HttpParameters extends DynamicParameters
{
    /**
     * @var string|null
     */
    protected $url;

    private function __construct(?string $url)
    {
        $this->url = $url;
    }

    public static function fromJsonString(string $json): DynamicParametersInterface
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

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self($in['url'] ?? null);
    }
}
