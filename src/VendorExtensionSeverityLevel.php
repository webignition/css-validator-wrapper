<?php

namespace webignition\CssValidatorWrapper;

class VendorExtensionSeverityLevel
{
    const LEVEL_IGNORE = 'ignore';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARN = 'warn';

    const VALID_VALUES = [
        self::LEVEL_IGNORE,
        self::LEVEL_WARN,
        self::LEVEL_ERROR,
    ];
}
