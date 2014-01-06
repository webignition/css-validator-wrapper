<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class IgnoreWarningsTest extends BaseTest {
    
    private $wrapper;       
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__);        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setBaseRequest($this->getHttpClient()->get());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();        
        $this->wrapper->setConfiguration($configuration);
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('three-vendor-extension-warnings.txt'));
    }

    public function testDisabled() {        
        $this->assertEquals(3, $this->wrapper->validate()->getWarningCount());
    }        
    
    public function testEnabled() {        
        $this->wrapper->getConfiguration()->setFlag(Flags::FLAG_IGNORE_WARNINGS);
        $this->assertEquals(0, $this->wrapper->validate()->getWarningCount());
    }
    
}