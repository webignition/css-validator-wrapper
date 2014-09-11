<?php

namespace webignition\Tests\HtmlValidator\Wrapper;

use webignition\Tests\CssValidatorWrapper\BaseTest;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorWrapper\Wrapper;

class CreateConfigurationTest extends BaseTest {
    
    public function testPassNonArrayArgumentThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(null);        
    }    
    

    public function testPassEmptyArrayArgumentThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array());        
    }    
    
    public function testMissingUrlToValidateThrowsInvalidArgumentException() {
        $this->setExpectedException('InvalidArgumentException');
        
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'foo' => 'bar'
        ));        
    }    
    
    public function testCreateConfigurationReturnsSelf() {
        $wrapper = new Wrapper();
        $this->assertInstanceOf('webignition\CssValidatorWrapper\Wrapper', $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/'
        )));        
    }
    
    
    public function testSetJavaExecutablePath() {
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'java-executable-path' => 'foo'
        ));         
        
        $this->assertEquals('foo', $wrapper->getConfiguration()->getJavaExecutablePath());        
    }
    
    public function testSetCssValidatorJarPath() {
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'css-validator-jar-path' => 'foo.jar'
        ));         
        
        $this->assertEquals('foo.jar', $wrapper->getConfiguration()->getCssValidatorJarPath());        
    }    
    
    public function testSetVendorExtensionSeverityLevel() {
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'vendor-extension-severity-level' => VendorExtensionSeverityLevel::LEVEL_ERROR
        ));         
        
        $this->assertEquals(VendorExtensionSeverityLevel::LEVEL_ERROR, $wrapper->getConfiguration()->getVendorExtensionSeverityLevel());        
    }
    
    public function testSetIgnoreWarningsFlag() {
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'flags' => array(
                Flags::FLAG_IGNORE_WARNINGS
            )
        ));         
        
        $this->assertTrue($wrapper->getConfiguration()->hasFlag(Flags::FLAG_IGNORE_WARNINGS));        
    }    
    
    public function testSetIgnoreFalseBackgroundImageDataUrlMessagesFlag() {
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'flags' => array(
                Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES
            )
        ));         
        
        $this->assertTrue($wrapper->getConfiguration()->hasFlag(Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES));        
    }      
    
    public function testSetDomainsToIgnore() {
        $domainsToIgnore = array(
            'foo',
            'bar'
        );
        
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'domains-to-ignore' => $domainsToIgnore
        ));         
        
        $this->assertEquals($domainsToIgnore, $wrapper->getConfiguration()->getDomainsToIgnore());        
    } 
    
    
    public function testSetBaseRequest() {
        $baseRequest = $this->getHttpClient()->get();
        $baseRequest->setAuth('example_username');
        
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'base-request' => $baseRequest
        ));         
        
        $this->assertEquals('example_username', $wrapper->getConfiguration()->getBaseRequest()->getUsername());
    }
    
    
    public function testSetContentToValidate() {        
        $content = 'foo';
        
        $wrapper = new Wrapper();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'content-to-validate' => $content
        ));         
        
        $this->assertEquals($content, $wrapper->getConfiguration()->getContentToValidate());
    }    

}