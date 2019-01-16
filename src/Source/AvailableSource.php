<?php

namespace webignition\CssValidatorWrapper\Source;

class AvailableSource extends AbstractSource
{
    private $localUri;

    public function __construct(string $uri, string $localUri)
    {
        parent::__construct($uri);
        $this->localUri = $localUri;
    }

    public function getLocalUri(): string
    {
        return $this->localUri;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
