<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpServerErrorTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }
    
    
    public function testHttp500WhenRetrievingRootWebResource() {
        $this->markTestSkipped('Not yet implemented');
    }   
    
    public function testHttp500WhenRetrievingCssResourceOneOfTwo() {
        $this->markTestSkipped('Not yet implemented');
    }   
    
    public function testHttp500WhenRetrievingCssResourceTwoOfTwo() {
        $this->markTestSkipped('Not yet implemented');
    }
    
}