<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration\Flags;

use webignition\CssValidatorWrapper\Configuration\Flags;

class IsValidTest extends \PHPUnit_Framework_TestCase {
    
    public function testAllValidValuesAreValid() {        
        foreach (Flags::getValidValues() as $validValue) {
            $this->assertTrue(Flags::isValid($validValue));
        }
    }
    
    
    public function testAllInvalidValuesAreInvalid() {        
        foreach (array('foo', 'bar') as $inValidValue) {
            $this->assertFalse(Flags::isValid($inValidValue));
        }
    }    
}