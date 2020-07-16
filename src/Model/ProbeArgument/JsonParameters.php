<?php
declare(strict_types=1);

namespace App\Model\ProbeArgument;

abstract class JsonParameters implements JsonParametersInterface, \ArrayAccess
{
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
    public function offsetGet($offset)
    {
        return $this->asArray()[$offset];
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
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }
}
