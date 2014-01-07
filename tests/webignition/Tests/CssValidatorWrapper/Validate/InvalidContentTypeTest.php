<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class InvalidContentTypeTest extends BaseTest {
    
    private $wrapper;
    
    public function setUp() {                       
        $this->setTestFixturePath(__CLASS__, $this->getName());        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setBaseRequest($this->getHttpClient()->get());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('no-messages.txt'));
    }

    public function testApplicationPdfRootWebResource() {
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertEquals('invalid-content-type:application/pdf', $output->getException()->getType()->get());
    }
    
    
    public function testTextPlainRootWebResource() {
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertEquals('invalid-content-type:text/plain', $output->getException()->getType()->get());
    }

    
    public function testTextPlainStylesheetResource() {
        $output = $this->wrapper->validate();
        
        $errorsForExceptionedStylesheet = $output->getErrorsByUrl('http://one.cdn.example.com/style.css');
        
        $this->assertEquals(1, count($errorsForExceptionedStylesheet));
        $this->assertEquals('invalid-content-type:text/plain', $errorsForExceptionedStylesheet[0]->getMessage());        
    }
     
}