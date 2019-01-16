<?php

namespace webignition\CssValidatorWrapper;

class SourceMap implements \ArrayAccess, \Iterator, \Countable
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

    public function getSourcePath(string $localPath): ?string
    {
        return array_search($localPath, $this->mappings);
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

    public function current(): string
    {
        return current($this->mappings);
    }

    public function next()
    {
        next($this->mappings);
    }

    public function key(): ?string
    {
        return key($this->mappings);
    }

    public function valid(): bool
    {
        return isset($this->mappings[$this->key()]);
    }

    public function rewind()
    {
        reset($this->mappings);
    }

    public function count(): int
    {
        return count($this->mappings);
    }
}
