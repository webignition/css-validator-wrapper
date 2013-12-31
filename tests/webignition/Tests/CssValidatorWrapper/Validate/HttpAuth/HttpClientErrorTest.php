<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpClientErrorTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }
    
    
    public function testHttp401WhenRetrievingRootWebResource() {
        $this->markTestSkipped('Not yet implemented');
    }
    
    public function testHttp404WhenRetrievingRootWebResource() {
        $this->markTestSkipped('Not yet implemented');
    }    
    
    public function testHttp401WhenRetrievingCssResourceOneOfTwo() {
        $this->markTestSkipped('Not yet implemented');
    }       
    
    public function testHttp404WhenRetrievingCssResourceOneOfTwo() {
        $this->markTestSkipped('Not yet implemented');
    }     
    
    public function testHttp401WhenRetrievingCssResourceTwoOfTwo() {
        $this->markTestSkipped('Not yet implemented');
    }       
    
    public function testHttp404WhenRetrievingCssResourceTwoOfTwo() {
        $this->markTestSkipped('Not yet implemented');
    }    
}