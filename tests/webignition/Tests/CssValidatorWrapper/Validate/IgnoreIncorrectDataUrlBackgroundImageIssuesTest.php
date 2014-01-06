<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class IgnoreIncorrectDataUrlBackgroundImageIssuesTest extends BaseTest {
    
    private $wrapper;    
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__);        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setBaseRequest($this->getHttpClient()->get());
        $this->wrapper->setConfiguration($configuration);
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('incorrect-data-url-background-image-errors.txt'));
    }

    public function testDisabled() {
        $this->assertEquals(3, $this->wrapper->validate()->getErrorCount());
    }        
    
    public function testEnabled() { 
        $this->wrapper->getConfiguration()->setFlag(Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES);        
        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
    }
    
}