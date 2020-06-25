<?php
declare(strict_types=1);

namespace App;

class PingArguments implements \ArrayAccess
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

    public static function fromJsonString(string $json): self
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

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->asArray()[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->asArray()[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }
}