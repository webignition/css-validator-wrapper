<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetCssValidatorPathTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultCssValdiatorPath() {        
        $configuration = new Configuration();
        $this->assertEquals(Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH, $configuration->getCssValidatorJarPath());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setCssValidatorJarPath(null));
    }
    
    
    public function testSetGetCssValdiatorPath() {        
        $cssValidatorPath = '/home/user/css-validator.jar';
        
        $configuration = new Configuration();
        $configuration->setCssValidatorJarPath($cssValidatorPath);
        $this->assertEquals($cssValidatorPath, $configuration->getCssValidatorJarPath());
    }    
    
}