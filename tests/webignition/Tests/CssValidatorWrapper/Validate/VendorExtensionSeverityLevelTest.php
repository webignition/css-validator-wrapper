<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class VendorExtensionSeverityLevelTest extends BaseTest {
    
    private $wrapper;       
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__);        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setBaseRequest($this->getHttpClient()->get());
        $this->wrapper->setConfiguration($configuration);
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-warnings.txt'));
    }

    public function testNotSet() {        
        $this->assertEquals(3, $this->wrapper->validate()->getWarningCount());        
    } 
    
    
    public function testIgnore() {
        $this->wrapper->getConfiguration()->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_IGNORE);
        $this->assertEquals(0, $this->wrapper->validate()->getWarningCount());   
    }
    
    public function testWarning() {
        $this->wrapper->getConfiguration()->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_WARN);
        $this->assertEquals(3, $this->wrapper->validate()->getWarningCount());                    
    }    
    
    public function testError() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-errors.txt'));
        $output = $this->wrapper->validate();
        
        $this->wrapper->getConfiguration()->setVendorExtensionSeverityLevel(VendorExtensionSeverityLevel::LEVEL_WARN);
        $this->assertEquals(0, $output->getWarningCount());      
        $this->assertEquals(3, $output->getErrorCount());
    }
    
}