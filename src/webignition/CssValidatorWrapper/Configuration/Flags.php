<?php

namespace webignition\CssValidatorWrapper\Configuration;

class Flags {
    
    const FLAG_IGNORE_WARNINGS = 'ignore-warnings';
    const FLAG_IGNORE_FALSE_BACKGROUND_IMAGE_DATA_URL_MESSAGES = 'ignore-false-background-data-url-messages';    
    
    /**
     *
     * @var array
     */
    private static $validValues = array(        
        self::FLAG_IGNORE_WARNINGS,
        self::FLAG_IGNORE_FALSE_BACKGROUND_IMAGE_DATA_URL_MESSAGES,
    );
    
    
    /**
     * 
     * @param string $severityLevel
     * @return boolean
     */
    public static function isValid($flag) {
        return in_array($flag, self::$validValues);
    }
    
    /**
     * 
     * @return array
     */
    public static function getValidValues() {
        return self::$validValues;
    }   
    
}