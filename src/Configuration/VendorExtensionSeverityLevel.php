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
    private static $validValues = array(
        self::LEVEL_ERROR,
        self::LEVEL_WARN,
        self::LEVEL_IGNORE
    );

    /**
     * @param string $severityLevel
     *
     * @return boolean
     */
    public static function isValid($severityLevel)
    {
        return in_array($severityLevel, self::$validValues);
    }

    /**
     * @return string[]
     */
    public static function getValidValues()
    {
        return self::$validValues;
    }
}
