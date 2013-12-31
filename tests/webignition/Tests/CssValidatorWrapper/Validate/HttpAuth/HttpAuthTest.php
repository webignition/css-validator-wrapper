<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpAuthTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }
    
    /**
     * test with curl error retrieving root web resource
     * test with http client error ...
     * test with http server error ...
     */

    public function testNotSet() {
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setCssValidatorJarPath('/home/jon/tools/css-validator/2002/css-validator/css-validator.jar');
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('http-auth-protocol-exception.txt'));
        $wrapper->setConfiguration($configuration);
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp401());
    }
    
    
    public function testSet() {         
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));        
        
        $wrapper->enableDeferToParentIfNoRawOutput();
        $wrapper->createConfiguration(array(
            'url-to-validate' => 'http://http-auth-01.simplytestable.com/',
            'css-validator-jar-path' => '/home/jon/tools/css-validator/2002/css-validator/css-validator.jar',
            'http-auth' => array(
                'user' => 'example',
                'password' => 'password'
            )
        ));
        
        $wrapper->setBaseRequest($this->getHttpClient()->get());
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $this->assertFalse($output->hasException());
        $this->assertEquals(3, $output->getErrorCount());
        
        foreach ($output->getMessages() as $message) {
            /* @var $message \webignition\CssValidatorOutput\Message\Error */
            $this->assertTrue(substr_count($message->getRef(), 'file:/') === 0);
        }
    }
    
}