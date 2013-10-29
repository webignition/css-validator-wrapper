<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetJavaExecutablePathTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultJavaExecutablePath() {        
        $configuration = new Configuration();
        $this->assertEquals(Configuration::DEFAULT_JAVA_EXECUTABLE_PATH, $configuration->getJavaExecutablePath());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setJavaExecutablePath(null));
    }
    
    
    public function testSetGetJavaExecutablePath() {        
        $javaExecutablePath = '/usr/bin/uncommon/java';
        
        $configuration = new Configuration();
        $configuration->setJavaExecutablePath($javaExecutablePath);
        $this->assertEquals($javaExecutablePath, $configuration->getJavaExecutablePath());
    }    
    
}