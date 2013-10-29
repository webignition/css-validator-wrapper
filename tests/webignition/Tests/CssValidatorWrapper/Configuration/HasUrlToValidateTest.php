<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class HasUrlToValidateTest extends \PHPUnit_Framework_TestCase {
    
    public function testFalseForDefaultUrl() {        
        $configuration = new Configuration();
        $this->assertFalse($configuration->hasUrlToValidate());
    }
    
    
    public function testTrueWhenHasUrlToValidate() {
        $configuration = new Configuration();
        $this->assertTrue($configuration->setUrlToValidate('http://example.com/')->hasUrlToValidate());
    }
    
    
    public function testFalseWhenNoUrlToValidate() {        
        $configuration = new Configuration();
        $this->assertFalse($configuration->setUrlToValidate('')->hasUrlToValidate());
    } 
    
}