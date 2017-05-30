<?php

namespace webignition\CssValidatorWrapper\Configuration;

class Flags
{
    const FLAG_IGNORE_WARNINGS = 'ignore-warnings';
    const FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES = 'ignore-false-background-data-url-messages';

    /**
     * @var string[]
     */
    private static $validValues = array(
        self::FLAG_IGNORE_WARNINGS,
        self::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
    );

    /**
     * @param string $flag
     *
     * @return bool
     */
    public static function isValid($flag)
    {
        return in_array($flag, self::$validValues);
    }

    /**
     * @return string[]
     */
    public static function getValidValues()
    {
        return self::$validValues;
    }
}
