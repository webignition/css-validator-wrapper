<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorOutput\Parser as CssValidatorOutputParser;

class Wrapper {
    
    const INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET = 1;
    const INVALID_ARGUMENT_EXCEPTION_URL_TO_VALIDATE_NOT_SET = 2;
    
    /**
     *
     * @var Configuration
     */
    private $configuration;
    
    
    /**
     * 
     * @param \webignition\CssValidatorWrapper\Configuration\Configuration $configuration
     * @return \webignition\CssValidatorWrapper\Wrapper
     */
    public function setConfiguration(Configuration $configuration) {
        $this->configuration = $configuration;
        return $this;
    }
    
    
    /**
     * 
     * @return Configuration
     */
    public function getConfiguration() {
        return $this->configuration;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasConfiguration() {
        return !is_null($this->getConfiguration());
    }
    
    
    /**
     * 
     * @param array $configurationValues
     * @return \webignition\CssValidatorWrapper\Wrapper
     * @throws \InvalidArgumentException
     */
    public function createConfiguration($configurationValues) {
        if (!is_array($configurationValues) || empty($configurationValues)) {
            throw new \InvalidArgumentException('A non-empty array of configuration values must be passed to create configuration', 2);
        }
        
        if (!isset($configurationValues['url-to-validate'])) {
            throw new \InvalidArgumentException('Configruation value "url-to-validate" not set', self::INVALID_ARGUMENT_EXCEPTION_URL_TO_VALIDATE_NOT_SET);
        }
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate($configurationValues['url-to-validate']);
        
        if (isset($configurationValues['java-executable-path'])) {
            $configuration->setJavaExecutablePath($configurationValues['java-executable-path']);
        }
        
        if (isset($configurationValues['css-validator-jar-path'])) {
            $configuration->setCssValidatorJarPath($configurationValues['css-validator-jar-path']);
        }
        
        if (isset($configurationValues['vendor-extension-severity-level'])) {
            $configuration->setVendorExtensionSeverityLevel($configurationValues['vendor-extension-severity-level']);
        }
        
        if (isset($configurationValues['flags']) && is_array($configurationValues['flags'])) {
            foreach ($configurationValues['flags'] as $flag) {
                $configuration->setFlag($flag);
            }
        }
        
        if (isset($configurationValues['domains-to-ignore']) && is_array($configurationValues['domains-to-ignore'])) {
            $configuration->setDomainsToIgnore($configurationValues['domains-to-ignore']);
        } 
        
        if (isset($configurationValues['http-auth']) && is_array($configurationValues['http-auth'])) {
            if (isset($configurationValues['http-auth']['user'])) {
                $configuration->setHttpAuthUser($configurationValues['http-auth']['user']);
            }
            
            if (isset($configurationValues['http-auth']['password'])) {
                $configuration->setHttpAuthPassword($configurationValues['http-auth']['password']);
            }
        }       
        
        $this->setConfiguration($configuration);
        return $this;
    }    
    
    
    /**
     * 
     * @return \webignition\CssValidatorOutput\CssValidatorOutput
     * @throws \InvalidArgumentException
     */
    public function validate() {
        if (!$this->hasConfiguration()) {
            throw new \InvalidArgumentException('Unable to validate; configuration not set', self::INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET);
        }
        
        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setRawOutput(implode("\n", $this->getRawValidatorOutputLines()));     
        
        if ($this->configuration->hasFlag(Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES)) {
            $cssValidatorOutputParser->setIgnoreFalseImageDataUrlMessages(true);
        }
        
        if ($this->configuration->hasFlag(Flags::FLAG_IGNORE_WARNINGS)) {
            $cssValidatorOutputParser->setIgnoreWarnings(true);            
        } 
        
        if ($this->configuration->getVendorExtensionSeverityLevel() === VendorExtensionSeverityLevel::LEVEL_IGNORE) {
            $cssValidatorOutputParser->setIgnoreVendorExtensionIssues(true);
        }
        
        if ($this->configuration->hasDomainsToIgnore()) {
            $cssValidatorOutputParser->setRefDomainsToIgnore($this->configuration->getDomainsToIgnore());
        }
        
        return $cssValidatorOutputParser->getOutput();       
    }
    
    
    /**
     * 
     * @return array
     */
    protected function getRawValidatorOutputLines() {
        $validatorOutputLines = array();
        exec($this->getConfiguration()->getExecutableCommand(), $validatorOutputLines);
        
        return $validatorOutputLines;
    }
    
}