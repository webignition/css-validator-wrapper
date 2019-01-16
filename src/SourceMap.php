<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Source\AvailableSource;
use webignition\CssValidatorWrapper\Source\SourceInterface;

class SourceMap implements \ArrayAccess, \Iterator, \Countable
{
    private $sources = [];

    /**
     * @param SourceInterface[] $sources
     */
    public function __construct(array $sources = [])
    {
        foreach ($sources as $source) {
            if ($source instanceof SourceInterface) {
                $this[$source->getUri()] = $source;
            }
        }
    }

    public function getByUri(string $uri): ?SourceInterface
    {
        return $this->offsetGet($uri);
    }

    public function getByLocalUri(string $localUri): ?SourceInterface
    {
        foreach ($this as $source) {
            if ($source->isAvailable() && $source instanceof AvailableSource && $localUri === $source->getLocalUri()) {
                return $source;
            }
        }

        return null;
    }

    public function offsetExists($offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }

        return isset($this->sources[$offset]);
    }

    public function offsetGet($offset): ?SourceInterface
    {
        return $this->sources[$offset] ?? null;
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

        if (!$value instanceof SourceInterface) {
            throw new \InvalidArgumentException('array value must be a SourceInterface instance');
        }

        $this->sources[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->sources[$offset]);
    }

    public function current(): SourceInterface
    {
        return current($this->sources);
    }

    public function next()
    {
        next($this->sources);
    }

    public function key(): ?string
    {
        return key($this->sources);
    }

    public function valid(): bool
    {
        return isset($this->sources[$this->key()]);
    }

    public function rewind()
    {
        reset($this->sources);
    }

    public function count(): int
    {
        return count($this->sources);
    }
}
