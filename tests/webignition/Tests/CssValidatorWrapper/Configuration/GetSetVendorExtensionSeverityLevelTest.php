<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class GetSetVendorExtensionSeverityLevelTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultVendorExtensionSeverityLevel() {        
        $configuration = new Configuration();
        $this->assertEquals(Configuration::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL, $configuration->getVendorExtensionSeverityLevel());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_WARN));
    }
    
    
    public function testSetValidValueThrowsNoException() {
        $configuration = new Configuration();
        
        try {
            foreach (VendorExtensionSeverityLevel::getValidValues() as $validValue) {
                $configuration->setVendorExtensionSeverityLevel($validValue);
            }            
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->fail('Unexpected InvalidArgumentException for $configuration->setVendorExtensionSeverityLevel(\''.$validValue.'\')');
        }
    }
    
    
    public function testSetNullValueThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $configuration = new Configuration();
        $configuration->setVendorExtensionSeverityLevel(null);
    }
    
    public function testSetInvalidValueThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $configuration = new Configuration();
        $configuration->setVendorExtensionSeverityLevel('foo');
    }   
    
}