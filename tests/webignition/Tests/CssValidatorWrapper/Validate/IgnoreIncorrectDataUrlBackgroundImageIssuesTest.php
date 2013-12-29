<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class IgnoreIncorrectDataUrlBackgroundImageIssuesTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }

    public function testDisabled() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('incorrect-data-url-background-image-errors.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(3, $output->getErrorCount());
    }        
    
    public function testEnabled() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setFlag(Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES);
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('incorrect-data-url-background-image-errors.txt'));
        
        
        $output = $wrapper->validate();
        $this->assertEquals(0, $output->getErrorCount());
    }
    
}