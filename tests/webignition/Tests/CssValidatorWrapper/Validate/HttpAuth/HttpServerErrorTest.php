<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpServerErrorTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }
    
    
    public function testHttp500WhenRetrievingRootWebResource() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper();
        
        $wrapper->createConfiguration(array(         
            'url-to-validate' => 'http://http-auth-01.simplytestable.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp500());  
    }   
    
    public function testHttp500WhenRetrievingCssResourceOneOfTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));                

        $wrapper->createConfiguration(array(          
            'url-to-validate' => 'http://http-auth-01.simplytestable.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://http-auth-01.simplytestable.com/assets/css/style1.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-500', $errorsForExceptionedUrl[0]->getMessage());
    }   
    
    public function testHttp500WhenRetrievingCssResourceTwoOfTwo() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        
        $wrapper->createConfiguration(array(           
            'url-to-validate' => 'http://http-auth-01.simplytestable.com/',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $errorsForExceptionedUrl = $output->getErrorsByUrl('http://http-auth-01.simplytestable.com/assets/css/style2.css');        
        
        $this->assertFalse($output->hasException());
        $this->assertEquals(1, count($errorsForExceptionedUrl));
        $this->assertEquals('http-error-500', $errorsForExceptionedUrl[0]->getMessage());
    }   
    
}