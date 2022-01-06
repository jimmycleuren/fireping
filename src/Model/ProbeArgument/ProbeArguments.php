<?php
declare(strict_types=1);

namespace App\Model\ProbeArgument;

abstract class ProbeArguments implements ProbeArgumentsInterface, \ArrayAccess
{
    abstract public static function fromJsonString(string $json): ProbeArgumentsInterface;
    abstract public function asArray(): array;

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset): mixed
    {
        return $this->asArray()[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->asArray()[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        $this->$offset = null;
    }
}
