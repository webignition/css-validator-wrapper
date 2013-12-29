<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\Flags;

class SetFlagTest extends \PHPUnit_Framework_TestCase {
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        
        $validFlags = Flags::getValidValues();
        $this->assertEquals($configuration, $configuration->setFlag($validFlags[0]));
    }    
    
    public function testSetValidValueThrowsNoException() {
        $configuration = new Configuration();
        
        try {
            foreach (Flags::getValidValues() as $validValue) {
                $configuration->setFlag($validValue);
            }            
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->fail('Unexpected InvalidArgumentException for $configuration->setFlag(\''.$validValue.'\')');
        }
    }
    
    public function testSetNullValueThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $configuration = new Configuration();
        $configuration->setFlag(null);
    }
    
    public function testSetInvalidValueThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $configuration = new Configuration();
        $configuration->setFlag('foo');
    }     
    
}