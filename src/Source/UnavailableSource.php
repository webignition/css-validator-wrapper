<?php

namespace webignition\CssValidatorWrapper\Source;

class UnavailableSource extends AbstractSource
{
    public function isAvailable(): bool
    {
        return false;
    }
}
