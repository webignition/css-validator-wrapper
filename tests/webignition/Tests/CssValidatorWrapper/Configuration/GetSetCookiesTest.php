<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use webignition\Tests\CssValidatorWrapper\BaseTest;
use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetCookiesTest extends BaseTest {
    
    public function testGetDefaultCookieCollection() {        
        $configuration = new Configuration();
        $this->assertEquals(array(), $configuration->getCookies());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setCookies(array()));
    }
    
    public function testSetGetCookies() {        
        $cookies = array(
            array(
                'name' => 'name1',
                'value' => 'value1'
            ),
            array(
                'domain' => '.example.com',
                'name' => 'name2',
                'value' => 'value2'
            ),
            array(
                'domain' => '.example.com',
                'secure' => true,
                'name' => 'name3',
                'value' => 'value3'
            )                        
        );
        
        $configuration = new Configuration();
        $configuration->setCookies($cookies);
        
        $this->assertEquals($cookies, $configuration->getCookies());
    }
    
}