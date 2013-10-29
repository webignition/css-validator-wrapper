<?php

namespace webignition\Tests\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\CssValidatorWrapper;

class HasConfigurationTest extends \PHPUnit_Framework_TestCase {
    
    public function testHasNotConfigurationByDefault() {
        $wrapper = new CssValidatorWrapper();
        $this->assertFalse($wrapper->hasConfiguration());
    }
    
    public function testHasConfigurationWhenConfigurationIsSet() {
        $wrapper = new CssValidatorWrapper();
        $this->assertTrue($wrapper->setConfiguration(new Configuration())->hasConfiguration());
    }    
    
}