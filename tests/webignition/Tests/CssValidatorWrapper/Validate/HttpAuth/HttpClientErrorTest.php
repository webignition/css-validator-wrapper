<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpClientErrorTest extends BaseTest {

    /**
     * @var CssValidatorWrapper
     */
    private $wrapper;
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-client' => $this->getHttpClient()
        ));
    }    
    
    public function testHttp401WhenRetrievingRootWebResource() {        
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp401());
    }
    
    public function testHttp404WhenRetrievingRootWebResource() {
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp404());
    }    
    
    public function testHttp401WhenRetrievingCssResourceOneOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        $output = $this->wrapper->validate();
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error:401', $errorsForExceptionedUrl[0]->getMessage());
    }       
    
    public function testHttp404WhenRetrievingCssResourceOneOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        $output = $this->wrapper->validate();
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error:404', $errorsForExceptionedUrl[0]->getMessage());
    }     
    
    public function testHttp401WhenRetrievingCssResourceTwoOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        $output = $this->wrapper->validate();
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error:401', $errorsForExceptionedUrl[0]->getMessage());
    }       
    
    public function testHttp404WhenRetrievingCssResourceTwoOfTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        $output = $this->wrapper->validate();
        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error:404', $errorsForExceptionedUrl[0]->getMessage());
    }    
    
    
    public function testHttp401WhenRetrievingCssResourcesOneAndTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        $output = $this->wrapper->validate();
        
        $errorsForStylesheet1 = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet1));
        $this->assertEquals('http-error:401', $errorsForStylesheet1[0]->getMessage());
        
        $errorsForStylesheet2 = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet2));
        $this->assertEquals('http-error:401', $errorsForStylesheet2[0]->getMessage());        
    } 
    
    
   public function testHttp404WhenRetrievingCssResourcesOneAndTwo() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        $output = $this->wrapper->validate();
        
        $errorsForStylesheet1 = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet1));
        $this->assertEquals('http-error:404', $errorsForStylesheet1[0]->getMessage());
        
        $errorsForStylesheet2 = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet2));
        $this->assertEquals('http-error:404', $errorsForStylesheet2[0]->getMessage());        
    }     
}