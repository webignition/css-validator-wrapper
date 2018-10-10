<?php

namespace webignition\CssValidatorWrapper\Configuration;

class VendorExtensionSeverityLevel
{
    const LEVEL_IGNORE = 'ignore';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARN = 'warn';

    /**
     * @var string[]
     */
    private static $validValues = [
        self::LEVEL_ERROR,
        self::LEVEL_WARN,
        self::LEVEL_IGNORE
    ];

    public static function isValid(string $severityLevel): bool
    {
        return in_array($severityLevel, self::$validValues);
    }

    /**
     * @return string[]
     */
    public static function getValidValues(): array
    {
        return self::$validValues;
    }
}
