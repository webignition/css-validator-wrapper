<?php

namespace webignition\CssValidatorWrapper\Source;

interface SourceInterface
{
    public function getUri(): string;
    public function isAvailable(): bool;
}
