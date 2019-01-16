<?php

namespace webignition\CssValidatorWrapper;

class Source
{
    private $uri;
    private $localUri;

    public function __construct(string $uri, ?string $localUri = null)
    {
        $this->uri = $uri;
        $this->localUri = $localUri;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getLocalUri(): ?string
    {
        return $this->localUri;
    }

    public function isAvailable(): bool
    {
        return !empty($this->localUri);
    }
}
