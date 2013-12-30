<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetHttpAuthPasswordTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultHttpAuthPasswordIsNull() {        
        $configuration = new Configuration();
        $this->assertNull($configuration->getHttpAuthPassword());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setHttpAuthPassword(null));
    }
    
    
    public function testSetGetHttpAuthPassword() {        
        $httpAuthPassword = 'foo';
        
        $configuration = new Configuration();
        $configuration->setHttpAuthPassword($httpAuthPassword);
        $this->assertEquals($httpAuthPassword, $configuration->getHttpAuthPassword());
    }    
    
}