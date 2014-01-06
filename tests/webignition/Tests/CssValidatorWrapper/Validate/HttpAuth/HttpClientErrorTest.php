<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpClientErrorTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }    
    
    public function testHttp401WhenRetrievingRootWebResource() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper();        
        
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp401());
    }
    
    public function testHttp404WhenRetrievingRootWebResource() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper();       
        
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp401());
    }    
    
    public function testHttp401WhenRetrievingCssResourceOneOfTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-401', $errorsForExceptionedUrl[0]->getMessage());
    }       
    
    public function testHttp404WhenRetrievingCssResourceOneOfTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();        
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-404', $errorsForExceptionedUrl[0]->getMessage());
    }     
    
    public function testHttp401WhenRetrievingCssResourceTwoOfTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-401', $errorsForExceptionedUrl[0]->getMessage());
    }       
    
    public function testHttp404WhenRetrievingCssResourceTwoOfTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        
        $wrapper->enableDeferToParentIfNoRawOutput();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-404', $errorsForExceptionedUrl[0]->getMessage());
    }    
    
    
    public function testHttp401WhenRetrievingCssResourcesOneAndTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        

        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        
        $errorsForStylesheet1 = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet1));
        $this->assertEquals('http-error-401', $errorsForStylesheet1[0]->getMessage());
        
        $errorsForStylesheet2 = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet2));
        $this->assertEquals('http-error-401', $errorsForStylesheet2[0]->getMessage());        
    } 
    
    
   public function testHttp404WhenRetrievingCssResourcesOneAndTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        
        $wrapper->enableDeferToParentIfNoRawOutput();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        
        $errorsForStylesheet1 = $output->getErrorsByUrl('http://example.com/assets/css/style1.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet1));
        $this->assertEquals('http-error-404', $errorsForStylesheet1[0]->getMessage());
        
        $errorsForStylesheet2 = $output->getErrorsByUrl('http://example.com/assets/css/style2.css');                
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForStylesheet2));
        $this->assertEquals('http-error-404', $errorsForStylesheet2[0]->getMessage());        
    }     
}