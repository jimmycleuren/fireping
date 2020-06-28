<?php
declare(strict_types=1);

namespace App\Model\ProbeArgument;

class PingArguments extends ProbeArguments
{
    /**
     * @var int|null
     */
    private $retries;
    /**
     * @var int|null
     */
    private $packetSize;

    private function __construct(?int $retries, ?int $packetSize)
    {
        $this->retries = $retries;
        $this->packetSize = $packetSize;
    }

    public static function fromJsonString(string $json): ProbeArgumentsInterface
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
}
