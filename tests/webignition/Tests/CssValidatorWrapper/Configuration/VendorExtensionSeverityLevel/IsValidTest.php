<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class IsValidTest extends \PHPUnit_Framework_TestCase {
    
    public function testAllValidValuesAreValid() {        
        foreach (VendorExtensionSeverityLevel::getValidValues() as $validValue) {
            $this->assertTrue(VendorExtensionSeverityLevel::isValid($validValue));
        }
    }
    
    
    public function testAllInvalidValuesAreInvalid() {        
        foreach (array('foo', 'bar') as $inValidValue) {
            $this->assertFalse(VendorExtensionSeverityLevel::isValid($inValidValue));
        }
    }    
}