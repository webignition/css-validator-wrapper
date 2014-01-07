<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
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

    public function testApplicationPdfRootWebResourceIsInvalid() {
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertEquals('invalid-content-type:application/pdf', $output->getException()->getType()->get());
    }
    
    
    public function testTextPlainRootWebResourceIsInvalid() {
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertEquals('invalid-content-type:text/plain', $output->getException()->getType()->get());
    }

    
    public function testTextPlainStylesheetResourceIsInvalid() {
        $output = $this->wrapper->validate();
        
        $errorsForExceptionedStylesheet = $output->getErrorsByUrl('http://one.cdn.example.com/style.css');
        
        $this->assertEquals(1, count($errorsForExceptionedStylesheet));
        $this->assertEquals('invalid-content-type:text/plain', $errorsForExceptionedStylesheet[0]->getMessage());        
    }
    
    
    public function testTextHtmlRootWebResourceIsValid() {
        $output = $this->wrapper->validate();
        $this->assertEquals(0, $output->getErrorCount());      
    }    
    
    public function testTextHtmlRootWebResourceWithCharsetAttributeIsValid() {
        $output = $this->wrapper->validate();
        $this->assertEquals(0, $output->getErrorCount());      
    }
    
    
    public function testMangledMarkupWithValidContentTypeDoesNotGenerateInvalidContentTypeError() {
        $output = $this->wrapper->validate();        
        $this->assertFalse($output->hasException());        
    }
     
}