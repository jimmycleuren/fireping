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

    public function __construct(?string $url)
    {
        $this->url = $url;
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

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
