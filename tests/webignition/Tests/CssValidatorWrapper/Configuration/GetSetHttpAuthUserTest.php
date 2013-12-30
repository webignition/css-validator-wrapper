<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetHttpAuthUserTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultHttpAuthUserIsNull() {        
        $configuration = new Configuration();
        $this->assertNull($configuration->getHttpAuthUser());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setHttpAuthUser(null));
    }
    
    
    public function testSetGetHttpAuthUser() {        
        $httpAuthUser = 'foo';
        
        $configuration = new Configuration();
        $configuration->setHttpAuthUser($httpAuthUser);
        $this->assertEquals($httpAuthUser, $configuration->getHttpAuthUser());
    }    
    
}