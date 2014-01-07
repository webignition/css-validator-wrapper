<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetContentToValidateTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultContentToValidate() {        
        $configuration = new Configuration();
        $this->assertNull($configuration->getContentToValidate());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setContentToValidate('foo'));
    }
    
    
    public function testSetGetContentToValidate() {        
        $content = 'foo';
        
        $configuration = new Configuration();
        $configuration->setContentToValidate($content);
        $this->assertEquals($content, $configuration->getContentToValidate());
    }
    
    public function testHasContentToValidateIsFalseWhenNoContentIsSet() {
        $configuration = new Configuration();
        $this->assertFalse($configuration->hasContentToValidate());        
    }

    public function testHasContentToValidateIsTrueWhenContentIsSet() {
        $configuration = new Configuration();
        $configuration->setContentToValidate('foo');
        $this->assertTrue($configuration->hasContentToValidate());        
    }    
}