<?php

namespace webignition\Tests\CssValidatorWrapper;

class GetSetJavaExecutablePathTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultJavaExecutablePath() {        
        $configuration = new \webignition\CssValidatorWrapper\Configuration();
        $this->assertEquals(\webignition\CssValidatorWrapper\Configuration::DEFAULT_JAVA_EXECUTABLE_PATH, $configuration->getJavaExecutablePath());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new \webignition\CssValidatorWrapper\Configuration();
        $this->assertEquals($configuration, $configuration->setJavaExecutablePath(null));
    }
    
    
    public function testSetGetDefaultJavaExecutablePath() {        
        $javaExecutablePath = '/usr/bin/uncommon/java';
        
        $configuration = new \webignition\CssValidatorWrapper\Configuration();
        $configuration->setJavaExecutablePath($javaExecutablePath);
        $this->assertEquals($javaExecutablePath, $configuration->getJavaExecutablePath());
    }    
    
}