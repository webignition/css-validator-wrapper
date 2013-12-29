<?php

namespace webignition\Tests\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;

class ValidateTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }     
    
    public function testValidateWithoutSettingConfigurationThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $wrapper = $this->getNewCssValidatorWrapper();        
        $wrapper->validate();
    }
    
//    public function testTest() {        
//        $configuration = new Configuration();
//        $configuration->setUrlToValidate('http://example.com/');
//        
//        $wrapper = $this->getNewCssValidatorWrapper(); 
//        $wrapper->setConfiguration($configuration);
//        $wrapper->setCssValidatorRawOutput($this->getFixture('output01.txt'));
//        
//        $wrapper->validate();
//    }
    
}