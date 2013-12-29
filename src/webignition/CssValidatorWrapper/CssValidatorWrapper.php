<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorOutput\Parser as CssValidatorOutputParser;

class CssValidatorWrapper {
    
    
    /**
     *
     * @var Configuration
     */
    private $configuration;
    
    
    /**
     * 
     * @param \webignition\CssValidatorWrapper\Configuration\Configuration $configuration
     * @return \webignition\CssValidatorWrapper\CssValidatorWrapper
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
     * @return \webignition\CssValidatorOutput\CssValidatorOutput
     * @throws \InvalidArgumentException
     */
    public function validate() {
        if (!$this->hasConfiguration()) {
            throw new \InvalidArgumentException('Unable to validate; configuration not set', 1);
        }
        
        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setRawOutput(implode("\n", $this->getRawValidatorOutputLines()));     
        
        if ($this->configuration->hasFlag(Flags::FLAG_IGNORE_FALSE_BACKGROUND_IMAGE_DATA_URL_MESSAGES)) {
            $cssValidatorOutputParser->setIgnoreFalseBackgroundImageDataUrlMessages(true);            
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