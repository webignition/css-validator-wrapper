<?php

namespace webignition\CssValidatorWrapper;

class Configuration {
    
    const JAVA_JAR_FLAG = '-jar';
    const DEFAULT_JAVA_EXECUTABLE_PATH = 'java';
    const DEFAULT_CSS_VALIDATOR_JAR_PATH = 'css-validator.jar';
    
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
    
}