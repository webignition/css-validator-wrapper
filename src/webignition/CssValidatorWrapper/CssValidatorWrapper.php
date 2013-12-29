<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
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
        
        
        var_dump("cp02");
        exit();
        
//        $validatorOutputLines = array();
//        exec($command, $validationOutputLines);
        
/**
        exec($command, $validationOutputLines);
               
        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setIgnoreFalseBackgroundImageDataUrlMessages(true);
        $cssValidatorOutputParser->setRawOutput(implode("\n", $validationOutputLines));
        
        if ($this->task->hasParameter('domains-to-ignore')) {
            $cssValidatorOutputParser->setRefDomainsToIgnore($this->task->getParameter('domains-to-ignore'));
        }
        
        if ($this->task->getParameter('vendor-extensions') == 'ignore') {    
            $cssValidatorOutputParser->setIgnoreVendorExtensionIssues(true);
        }
        
        if ($this->task->getParameter('vendor-extensions') == 'warn' && $this->task->isTrue('ignore-warnings')) {
            $cssValidatorOutputParser->setIgnoreVendorExtensionIssues(true);
        }        
        
        $cssValidatorOutput = $cssValidatorOutputParser->getOutput();
        
        if ($cssValidatorOutput->getIsUnknownMimeTypeError()) {
            $this->response->setHasBeenSkipped();
            $this->response->setErrorCount(0);
            $this->response->setIsRetryable(false);
            return true;            
        } 
        
        if ($cssValidatorOutput->getIsSSlExceptionErrorOutput()) {
            $this->response->setHasFailed();
            $this->response->setErrorCount(1); 
            $this->response->setIsRetryable(false);
            return json_encode($this->getSslExceptionErrorOutput($this->task));
        }
        
        
        if ($cssValidatorOutput->getIsUnknownExceptionError()) {            
            $this->response->setHasFailed();
            $this->response->setErrorCount(1); 
            $this->response->setIsRetryable(false);
            return json_encode($this->getUnknownExceptionErrorOutput($this->task));
        }        
        
        $this->response->setErrorCount($cssValidatorOutput->getErrorCount());
        $this->response->setWarningCount($cssValidatorOutput->getWarningCount());
        
        return $this->getSerializer()->serialize($cssValidatorOutput->getMessages(), 'json');    
 */        
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