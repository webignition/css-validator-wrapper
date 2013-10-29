<?php

namespace webignition\CssValidatorWrapper\Configuration;

class Configuration {
    
    const JAVA_JAR_FLAG = '-jar';
    const DEFAULT_JAVA_EXECUTABLE_PATH = 'java';
    const DEFAULT_CSS_VALIDATOR_JAR_PATH = 'css-validator.jar';
    const DEFAULT_OUTPUT_FORMAT = 'ucn';
    const DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL = VendorExtensionSeverityLevel::LEVEL_WARN;    
    
    
    /**
     *
     * @var string
     */
    private $javaExecutablePath = null;
    
    
    /**
     *
     * @var string
     */
    private $cssValidatorJarPath = null;
    
    
    /**
     *
     * @var string
     */
    private $vendorExtensionSeverityLevel = null;
    
    
    /**
     * 
     * @param string $javaExecutablePath
     * @return \webignition\CssValidatorWrapper\Configuration
     */
    public function setJavaExecutablePath($javaExecutablePath) {
        $this->javaExecutablePath = $javaExecutablePath;
        return $this;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getJavaExecutablePath() {
        return (is_null($this->javaExecutablePath)) ? self::DEFAULT_JAVA_EXECUTABLE_PATH : $this->javaExecutablePath;
    }
    
    
    /**
     * 
     * @param string $cssValidatorJarPath
     * @return \webignition\CssValidatorWrapper\Configuration
     */
    public function setCssValidatorJarPath($cssValidatorJarPath) {
        $this->cssValidatorJarPath = $cssValidatorJarPath;
        return $this;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getCssValidatorJarPath() {
        return (is_null($this->cssValidatorJarPath)) ? self::DEFAULT_CSS_VALIDATOR_JAR_PATH : $this->cssValidatorJarPath; 
    }
    
    
    /**
     * 
     * @return string
     */
    public function getOutputFormat() {
        return self::DEFAULT_OUTPUT_FORMAT;
    }
    
    
    /**
     * 
     * @param string $vendorExtensionSeverityLevel
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     * @throws \InvalidArgumentException
     */
    public function setVendorExtensionSeverityLevel($vendorExtensionSeverityLevel) {
        if (!VendorExtensionSeverityLevel::isValid($vendorExtensionSeverityLevel)) {
            throw new \InvalidArgumentException('Invalid severity level, must be one of ['.  implode(', ', VendorExtensionSeverityLevel::getValidValues()).']', 1);
        }
        
        $this->vendorExtensionSeverityLevel = $vendorExtensionSeverityLevel;
        return $this;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getVendorExtensionSeverityLevel() {
        return (is_null($this->vendorExtensionSeverityLevel)) ? self::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL : $this->vendorExtensionSeverityLevel;
    }
    
}