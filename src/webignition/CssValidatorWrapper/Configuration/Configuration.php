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
     * @var string
     */
    private $urlToValidate = null;
    
    
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
    
    
    /**
     * 
     * @param string $url
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     */
    public function setUrlToValidate($url) {
        $this->urlToValidate = trim($url);
        return $this;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getUrlToValidate() {
        return (is_null($this->urlToValidate)) ? '' : $this->urlToValidate;
    }
    
    
    public function getExecutableCommand() {
        if (!$this->hasUrlToValidate()) {
            throw new \InvalidArgumentException('URL to validate has not been set', 2);
        }
        
        $preparedUrl = str_replace('"', '\"', $this->getUrlToValidate());
        
        $command = $this->getJavaExecutablePath().' '.self::JAVA_JAR_FLAG.' '.$this->getCssValidatorJarPath().' '.$this->getCommandOptionsString().' "'.$preparedUrl.'" 2>&1';
        
        var_dump($command);
        exit();
        
/**
        $commandOptions = array(
            'output' => 'ucn'
        );
        
        if ($this->task->getParameter('vendor-extensions') == 'warn') {
            $commandOptions['vextwarning'] = 'true';
        }
        
        if ($this->task->getParameter('vendor-extensions') == 'error') {
            $commandOptions['vextwarning'] = 'false';
        }        
        
        $commandOptionsStrings = '';
        foreach ($commandOptions as $key => $value) {
            $commandOptionsStrings[] = '-'.$key.' '.$value;
        }
        
        $preparedUrl = str_replace('"', '\"', $this->webResource->getUrl());        
        
        $validationOutputLines = array();
        $command = "java -jar ".$this->getProperty('jar-path')." ".  implode(' ', $commandOptionsStrings)." \"" . $preparedUrl ."\" 2>&1";               
 */        
    }
    
    
    public function hasUrlToValidate() {
        return $this->getUrlToValidate() != '';
    }
    
    
    
    private function getCommandOptionsString() {
        $commandOptionsStrings = array();
        foreach ($this->getCommandOptions() as $key => $value) {
            $commandOptionsStrings[] = '-'.$key.' '.$value;
        }  
        
        return implode(' ', $commandOptionsStrings);
    }
    
    
    private function getCommandOptions() {
        $commandOptions = array(
            'output' => $this->getOutputFormat(),            
        );
        
        if ($this->getVendorExtensionSeverityLevel() == VendorExtensionSeverityLevel::LEVEL_WARN) {
            $commandOptions['vextwarning'] = 'true';
        } else {
            $commandOptions['vextwarning'] = 'false';
        }
        
        return $commandOptions;
    }
    
}