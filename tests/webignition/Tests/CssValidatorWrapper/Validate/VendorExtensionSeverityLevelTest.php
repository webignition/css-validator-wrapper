<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class VendorExtensionSeverityLevelTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }

    public function testNotSet() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-warnings.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(3, $output->getWarningCount());        
    } 
    
    
    public function testIgnore() {
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_IGNORE);
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-warnings.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(0, $output->getWarningCount());        
    }
    
    public function testWarning() {
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_WARN);
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-warnings.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(3, $output->getWarningCount());            
    }    
    
    public function testError() {
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_ERROR);
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-errors.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(0, $output->getWarningCount());
        $this->assertEquals(3, $output->getErrorCount());
    }
    
}