<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\HttpAuth;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class CurlErrorTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }
    
    
    public function testCurlCouldNotResolveHostWhenRetrievingRootWebResource() {
        // curl code 6
        $this->markTestSkipped('Not yet implemented');
    }   
    
    public function testCurlTimeoutWhenRetrievingRootWebResource() {
        // curl code 28
        $this->markTestSkipped('Not yet implemented');
    } 
    
    
    public function testCurlCouldNotResolveHostWhenRetrievingCssResourceOneOfTwo() {
        // curl code 6
        $this->markTestSkipped('Not yet implemented');
    }   
    
    public function testCurlTimeoutWhenRetrievingCssResourceOneOfTwo() {
        // curl code 28
        $this->markTestSkipped('Not yet implemented');
    }
    
    public function testCurlCouldNotResolveHostWhenRetrievingCssResourceTwoOfTwo() {
        // curl code 6
        $this->markTestSkipped('Not yet implemented');
    }   
    
    public function testCurlTimeoutWhenRetrievingCssResourceTwoOfTwo() {
        // curl code 28
        $this->markTestSkipped('Not yet implemented');
    }

    
}