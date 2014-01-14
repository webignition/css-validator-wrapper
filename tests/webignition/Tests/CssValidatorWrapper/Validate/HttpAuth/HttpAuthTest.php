<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpAuthTest extends BaseTest {
    
    private $wrapper;
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->createConfiguration(array(
            'url-to-validate' => 'http://example.com/',
            'base-request' => $this->getHttpClient()->get()
        )); 
    }

    public function testNotSet() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('http-auth-protocol-exception.txt'));
        $output = $this->wrapper->validate();
        
        $this->assertTrue($output->hasException());
        $this->assertTrue($output->getException()->isHttp401());
    }
    
    
    public function testSet() {
        $baseRequest = $this->getHttpClient()->get();
        $baseRequest->setAuth('example', 'password', 'any');
        $this->wrapper->getConfiguration()->setBaseRequest($baseRequest);
        
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('CssValidatorResponse/1'));#

        $output = $this->wrapper->validate();
        $this->assertFalse($output->hasException());
        $this->assertEquals(3, $output->getErrorCount());
        
        foreach ($output->getMessages() as $message) {
            $this->assertTrue(substr_count($message->getRef(), 'file:/') === 0);
        }
    }
    
}