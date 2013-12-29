<?php

namespace webignition\Tests\CssValidatorWrapper;

use webignition\Tests\CssValidatorWrapper\BaseTest;

class ConfigurationTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }     
    
    public function testValidateWithoutSettingConfigurationThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $wrapper = $this->getNewCssValidatorWrapper();        
        $wrapper->validate();
    }
    
}