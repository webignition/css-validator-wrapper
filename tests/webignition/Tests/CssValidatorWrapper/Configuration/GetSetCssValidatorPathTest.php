<?php

namespace webignition\Tests\CssValidatorWrapper;

class GetSetCssValidatorPathTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultCssValdiatorPath() {        
        $configuration = new \webignition\CssValidatorWrapper\Configuration();
        $this->assertEquals(\webignition\CssValidatorWrapper\Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH, $configuration->getCssValidatorJarPath());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new \webignition\CssValidatorWrapper\Configuration();
        $this->assertEquals($configuration, $configuration->setCssValidatorJarPath(null));
    }
    
    
    public function testSetGetCssValdiatorPath() {        
        $cssValidatorPath = '/home/user/css-validator.jar';
        
        $configuration = new \webignition\CssValidatorWrapper\Configuration();
        $configuration->setCssValidatorJarPath($cssValidatorPath);
        $this->assertEquals($cssValidatorPath, $configuration->getCssValidatorJarPath());
    }    
    
}