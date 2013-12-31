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
     * @var array
     */
    private $flags = array();
    
    
    /**
     *
     * @var array
     */
    private $domainsToIgnore = array();
    
    
    /**
     *
     * @var string
     */
    private $httpAuthUser = null;
    
    
    /**
     *
     * @var string
     */
    private $httpAuthPassword = null;
    
    
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
        
        $commandParts = array(
            $this->getJavaExecutablePath(),
            self::JAVA_JAR_FLAG,
            $this->getCssValidatorJarPath(),
            $this->getCommandOptionsString(),
            '"'.str_replace('"', '\"', $this->getUrlToValidate()).'"',
            '2>&1'
        );
        
        return implode(' ', $commandParts); 
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
    
    
    
    /**
     * 
     * @param string $flag
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     * @throws \InvalidArgumentException
     */
    public function setFlag($flag) {
        if (!Flags::isValid($flag)) {
            throw new \InvalidArgumentException('Invalid flag, must be one of ['.  implode(', ', VendorExtensionSeverityLevel::getValidValues()).']', 2);
        }
        
        $this->flags[$flag] = true;
        return $this;
    }
    
    
    /**
     * 
     * @param string $flag
     * @return boolean
     */
    public function hasFlag($flag) {
        return isset($this->flags[$flag]);
    }
        
    
    /**
     * 
     * @param string $flag
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     */
    public function clearFlag($flag) {
        if ($this->hasFlag($flag)) {
            unset($this->flags[$flag]);
        }
        
        return $this;
    }
    
    
    /**
     * 
     * @param array $domainsToIgnore
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     */
    public function setDomainsToIgnore($domainsToIgnore) {
        $this->domainsToIgnore = $domainsToIgnore;
        return $this;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getDomainsToIgnore() {
        return $this->domainsToIgnore;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasDomainsToIgnore() {        
        return is_array($this->getDomainsToIgnore()) && count($this->getDomainsToIgnore()) > 0;
    }
    
    
    /**
     * 
     * @param string $httpAuthUser
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     */
    public function setHttpAuthUser($httpAuthUser) {
        $this->httpAuthUser = $httpAuthUser;
        return $this;
    }
    
    
    /**
     * 
     * @return string|null
     */
    public function getHttpAuthUser() {
        return $this->httpAuthUser;
    }
    
    
    /**
     * 
     * @param string $httpAuthPassword
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     */
    public function setHttpAuthPassword($httpAuthPassword) {
        $this->httpAuthPassword = $httpAuthPassword;
        return $this;
    }
    
    
    /**
     * 
     * @return string|null
     */    
    public function getHttpAuthPassword() {
        return $this->httpAuthPassword;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasHttpAuthCredentials() {
        return is_string($this->getHttpAuthUser()) || is_string($this->getHttpAuthPassword());
    }
    
}