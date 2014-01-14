<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\Tests\CssValidatorWrapper\BaseTest;

class CurlErrorTest extends BaseTest {
    
    private $wrapper;
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $this->wrapper = $this->getNewCssValidatorWrapper();        
        
        $this->wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            ),
            'base-request' => $this->getHttpClient()->get()
        ));
        
        $this->wrapper->getConfiguration()->setCssValidatorJarPath('/home/jon/tools/css-validator/2002/css-validator/css-validator.jar');        
        $this->wrapper->enableDeferToParentIfNoRawOutput();
    }
    
    
    public function testCurlCouldNotResolveHostWhenRetrievingRootWebResource() {
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isCurl6());        
    }   
    
    public function testCurlTimeoutWhenRetrievingRootWebResource() {        
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isCurl28());    
    } 
    
    
    public function testCurlCouldNotResolveHostWhenRetrievingCssResourceOneOfTwo() {        
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1')); 
        $output = $this->wrapper->validate();        
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('curl-error:6', $errorsForExceptionedUrl[0]->getMessage());
    }   
    
    public function testCurlTimeoutWhenRetrievingCssResourceOneOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1')); 
        $output = $this->wrapper->validate(); 
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('curl-error:28', $errorsForExceptionedUrl[0]->getMessage());
    }
    
    public function testCurlCouldNotResolveHostWhenRetrievingCssResourceTwoOfTwo() { 
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1')); 
        $output = $this->wrapper->validate(); 
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('curl-error:6', $errorsForExceptionedUrl[0]->getMessage());
    }   
    
    public function testCurlTimeoutWhenRetrievingCssResourceTwoOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1')); 
        $output = $this->wrapper->validate(); 
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('curl-error:28', $errorsForExceptionedUrl[0]->getMessage());
    }   

    
}