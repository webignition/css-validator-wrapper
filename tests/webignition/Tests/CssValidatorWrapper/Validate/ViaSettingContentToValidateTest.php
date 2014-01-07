<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class ViaSettingContentToValidateTest extends BaseTest {
    
    private $wrapper;
    
    public function setUp() {                       
        $this->setTestFixturePath(__CLASS__);
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setBaseRequest($this->getHttpClient()->get());        
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);        
        $this->wrapper->enableDeferToParentIfNoRawOutput();
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('no-messages.txt'));
    }
    
    public function testWithHtmlContentWithStylesheets() {      
        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-with-stylesheets.html'));
        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
    }            

    public function testWithHtmlContentWithNoStylesheets() {      
        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-no-stylesheets.html'));
        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
    }        
    
    public function testWithCssContent() {      
        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content.css'));
        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
    }
    
}