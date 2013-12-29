<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class IgnoreWarningsTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }

    public function testDisabled() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('single-warning.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(1, $output->getWarningCount());
    }        
    
    public function testEnabled() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setFlag(Flags::FLAG_IGNORE_WARNINGS);
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('single-warning.txt'));
        
        
        $output = $wrapper->validate();
        $this->assertEquals(0, $output->getWarningCount());
    }
    
    public function testEnabledWithVendorExtensionSeverityLevelWarn() {
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setFlag(Flags::FLAG_IGNORE_WARNINGS);
        $configuration->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_WARN);
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-warnings.txt'));        
        
        $output = $wrapper->validate();
        $this->assertEquals(0, $output->getWarningCount());        
    }
    
}