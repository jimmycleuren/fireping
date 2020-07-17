<?php
declare(strict_types=1);

namespace App\Model\Parameter\Probe;

use App\Model\Parameter\DynamicParameters;
use App\Model\Parameter\DynamicParametersInterface;

class PingParameters extends DynamicParameters
{
    /**
     * @var int|null
     */
    protected $retries;
    /**
     * @var int|null
     */
    protected $packetSize;

    public function __construct(?int $retries, ?int $packetSize)
    {
        $this->retries = $retries;
        $this->packetSize = $packetSize;
    }

    public static function fromJsonString(string $json): DynamicParametersInterface
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return new self($data['retries'] ?? null, $data['packetSize'] ?? null);
    }

    public function asArray(): array
    {
        return [
            'retries' => $this->retries,
            'packetSize' => $this->packetSize
        ];
    }

    public static function fromArray(array $in): DynamicParametersInterface
    {
        return new self($in['retries'] ?? null, $in['packetSize'] ?? null);
    }
}