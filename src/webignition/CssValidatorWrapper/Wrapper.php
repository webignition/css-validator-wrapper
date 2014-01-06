<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorOutput\Parser\Parser as CssValidatorOutputParser;
use webignition\CssValidatorOutput\Parser\Configuration as CssValidatorOutputParserConfiguration;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\ExceptionOutput\ExceptionOutput;
use webignition\CssValidatorOutput\ExceptionOutput\Type\Type as ExceptionOutputType;

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
     * @var \Guzzle\Http\Message\Request
     */
    private $baseRequest = null;

    
    /**
     *
     * @var LocalProxyResource
     */
    protected $localProxyResource = null;
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     */
    public function setBaseRequest(\Guzzle\Http\Message\Request $request) {
        $this->baseRequest = $request;
    }
    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\Request $request
     */
    public function getBaseRequest() {
        if (is_null($this->baseRequest)) {
            $client = new \Guzzle\Http\Client;            
            $this->baseRequest = $client->get();
        }
        
        return $this->baseRequest;
    }    
    
    
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
        
        if ($this->getConfiguration()->hasHttpAuthCredentials()) {
            try { 
                $this->createLocalProxyResource();                         
                $this->getLocalProxyResource()->prepare();
            } catch (\webignition\WebResource\Exception\Exception $webResourceException) {                
                $cssValidatorOutput = new CssValidatorOutput();
                $cssValidatorOutputException = new ExceptionOutput();
                $cssValidatorOutputException->setType(new ExceptionOutputType('http' . $webResourceException->getResponse()->getStatusCode()));
                
                $cssValidatorOutput->setException($cssValidatorOutputException);
                return $cssValidatorOutput;
            } catch (\Guzzle\Http\Exception\CurlException $curlException) {                
                $cssValidatorOutput = new CssValidatorOutput();
                $cssValidatorOutputException = new ExceptionOutput();
                $cssValidatorOutputException->setType(new ExceptionOutputType('curl' . $curlException->getErrorNo()));
                
                $cssValidatorOutput->setException($cssValidatorOutputException);
                return $cssValidatorOutput;            
            }
        }
        
        $cssValidatorOutputParserConfiguration = new CssValidatorOutputParserConfiguration();
        $validatorOutput = implode("\n", $this->getRawValidatorOutputLines());
        
        if ($this->hasLocalProxyResource()) {
            $validatorOutput = $this->replaceLocalFilePathsWithOriginalFilePaths($validatorOutput);           
        }        
        
        $cssValidatorOutputParserConfiguration->setRawOutput($validatorOutput);
        
        if ($this->hasLocalProxyResource()) {
            $this->localProxyResource->clear();
        }
        
        if ($this->getConfiguration()->hasFlag(Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES)) {
            $cssValidatorOutputParserConfiguration->setIgnoreFalseImageDataUrlMessages(true);
        }
        
        if ($this->getConfiguration()->hasFlag(Flags::FLAG_IGNORE_WARNINGS)) {
            $cssValidatorOutputParserConfiguration->setIgnoreWarnings(true);            
        } 
        
        if ($this->getConfiguration()->getVendorExtensionSeverityLevel() === VendorExtensionSeverityLevel::LEVEL_IGNORE) {
            $cssValidatorOutputParserConfiguration->setIgnoreVendorExtensionIssues(true);
        }
        
        if ($this->getConfiguration()->hasDomainsToIgnore()) {
            $cssValidatorOutputParserConfiguration->setRefDomainsToIgnore($this->getConfiguration()->getDomainsToIgnore());
        }        
        
        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setConfiguration($cssValidatorOutputParserConfiguration);
        
        $output = $cssValidatorOutputParser->getOutput();
        
        if ($this->hasLocalProxyResource()) {           
            if ($this->getLocalProxyResource()->hasWebResourceExceptions()) {
                foreach ($this->getLocalProxyResource()->getWebResourceExceptions() as $webResourceException) {
                    $error = new \webignition\CssValidatorOutput\Message\Error();
                    $error->setContext('');
                    $error->setLineNumber(0);
                    $error->setMessage('http-error-' . $webResourceException->getResponse()->getStatusCode());
                    $error->setRef($webResourceException->getRequest()->getUrl());
                    
                    $output->addMessage($error);
                }
            } 
            
            if ($this->getLocalProxyResource()->hasCurlExceptions()) {
                foreach ($this->getLocalProxyResource()->getCurlExceptions() as $curlExceptionDetails) {
                    $error = new \webignition\CssValidatorOutput\Message\Error();
                    $error->setContext('');
                    $error->setLineNumber(0);
                    $error->setMessage('curl-error-' . $curlExceptionDetails['exception']->getErrorNo());
                    $error->setRef($curlExceptionDetails['url']);
                    
                    $output->addMessage($error);
                }
            }
        } 
        
        return $cssValidatorOutputParser->getOutput();       
    }
    
    
    protected function createLocalProxyResource() {
        $this->localProxyResource = new LocalProxyResource($this->getConfiguration(), $this->getBaseRequest());
    }
    
    
    /**
     * 
     * @return LocalProxyResource
     */
    protected function getLocalProxyResource() {
        return $this->localProxyResource;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasLocalProxyResource() {
        return !is_null($this->localProxyResource);
    }
    
    
    /**
     * 
     * @return array
     */
    protected function getRawValidatorOutputLines() {
        $validatorOutputLines = array();
        
        $executableCommand = $this->hasLocalProxyResource() 
            ? $this->getLocalProxyResource()->getConfiguration()->getExecutableCommand()
            : $this->getConfiguration()->getExecutableCommand();
        
        exec($executableCommand, $validatorOutputLines);        
        return $validatorOutputLines;
    }
    
    
    
    /**
     * 
     * @param string $validatorOutputLines
     * @return string
     */
    private function replaceLocalFilePathsWithOriginalFilePaths($validatorOutput) {        
        $refMatches = array();
        preg_match_all('/ref="file:\/tmp\/[^"]*"/', $validatorOutput, $refMatches);        
        
        if (count($refMatches) > 0) {
            $refAttributes = $refMatches[0];
            
            foreach ($refAttributes as $refAttribute) {                
                $originalUrl = $this->localProxyResource->getWebResourceUrlFromPath(str_replace(array('ref="file:', '"'), '', $refAttribute));
                $validatorOutput = str_replace($refAttribute, 'ref="' . $originalUrl . '"', $validatorOutput);
            }
        }
        
        return $validatorOutput;
    }
    
}