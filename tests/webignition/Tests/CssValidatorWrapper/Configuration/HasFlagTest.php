<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\Flags;

class HasFlagTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultHasNoFlags() {
        $configuration = new Configuration();
        
        foreach (Flags::getValidValues() as $flag) {
            $this->assertFalse($configuration->hasFlag($flag));
        } 
    }
    
    public function testSetAllHasAll() {
        $configuration = new Configuration();
        
        foreach (Flags::getValidValues() as $flag) {
            $configuration->setFlag($flag);
            $this->assertTrue($configuration->hasFlag($flag));
        } 
    }    
    
    public function testSetAllClearAllHasNone() {
        $configuration = new Configuration();
        
        foreach (Flags::getValidValues() as $flag) {
            $configuration->setFlag($flag);
            $configuration->clearFlag($flag);
            $this->assertFalse($configuration->hasFlag($flag));
        }
    }    
    
}