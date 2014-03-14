<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use webignition\Tests\CssValidatorWrapper\BaseTest;
use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetBaseRequestTest extends BaseTest {
    
    public function testGetDefaultBaseRequest() {        
        $configuration = new Configuration();        
        $this->assertInstanceOf('\Guzzle\Http\Message\Request', $configuration->getBaseRequest());
    }

    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setBaseRequest($this->getHttpClient()->get()));
    }
    
    public function testSetGetBaseRequest() {        
        $baseRequest = $this->getHttpClient()->get();
        $baseRequest->setAuth('example_user', 'example_password');
        
        $configuration = new Configuration();
        $configuration->setBaseRequest($baseRequest);
        
        $this->assertEquals('example_user', $configuration->getBaseRequest()->getUsername());
        $this->assertEquals($baseRequest->getUsername(), $configuration->getBaseRequest()->getUsername());
    }
    
}