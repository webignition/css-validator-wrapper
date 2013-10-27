<?php

namespace webignition\CssValidatorWrapper;

class Configuration {
    
    const JAVA_JAR_FLAG = '-jar';
    const DEFAULT_JAVA_EXECUTABLE_PATH = 'java';
    
    
    /**
     *
     * @var string
     */
    private $javaExecutablePath = null;
    
    
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
}