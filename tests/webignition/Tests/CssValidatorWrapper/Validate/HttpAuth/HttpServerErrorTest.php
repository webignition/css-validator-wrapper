<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpServerErrorTest extends BaseTest {
    
    private $wrapper;    
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/'
        ));
        
        $this->wrapper->setBaseRequest($this->getHttpClient()->get());            
    }
    
    
    public function testHttp500WhenRetrievingRootWebResource() {
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp500());  
    }   
    
    public function testHttp500WhenRetrievingCssResourceOneOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));

        $output = $this->wrapper->validate();
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-500', $errorsForExceptionedUrl[0]->getMessage());
    }   
    
    public function testHttp500WhenRetrievingCssResourceTwoOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));

        $output = $this->wrapper->validate();
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-500', $errorsForExceptionedUrl[0]->getMessage());
    }   
    
}