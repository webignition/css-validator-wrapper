<?php

namespace webignition\CssValidatorWrapper\Source;

abstract class AbstractSource implements SourceInterface
{
    private $uri;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
