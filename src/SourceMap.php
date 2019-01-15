<?php

namespace webignition\CssValidatorWrapper;

class SourceMap implements \ArrayAccess
{
    private $mappings = [];

    public function __construct(array $mappings = [])
    {
        foreach ($mappings as $sourcePath => $localPath) {
            $this[$sourcePath] = $localPath;
        }
    }

    public function getLocalPath(string $sourcePath): ?string
    {
        return $this->mappings[$sourcePath] ?? null;
    }

    public function offsetExists($offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }

        return isset($this->mappings[$offset]);
    }

    public function offsetGet($offset): ?string
    {
        return $this->mappings[$offset] ?? null;
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        if (!is_string($offset)) {
            throw new \InvalidArgumentException('array key must be a string');
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('array value must be a string');
        }

        $this->mappings[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->mappings[$offset]);
    }
}
