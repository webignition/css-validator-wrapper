<?php

namespace webignition\Tests\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;

class SetConfigurationTest extends \PHPUnit_Framework_TestCase {
    
    public function testSetConfigurationReturnsSelf() {
        $wrapper = new CssValidatorWrapper();
        $this->assertEquals($wrapper, $wrapper->setConfiguration(new Configuration()));        
    }
    
}