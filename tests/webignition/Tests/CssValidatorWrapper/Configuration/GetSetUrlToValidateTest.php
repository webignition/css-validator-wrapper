<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetUrlToValidateTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultUrlToValidate() {        
        $configuration = new Configuration();
        $this->assertEquals('', $configuration->getUrlToValidate());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setUrlToValidate('http://example.com/'));
    }
    
    
    public function testSetGetUrlToValidate() {        
        $urlToValidate = 'http://example.com/';
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate($urlToValidate);
        $this->assertEquals($urlToValidate, $configuration->getUrlToValidate());
    } 
    
}