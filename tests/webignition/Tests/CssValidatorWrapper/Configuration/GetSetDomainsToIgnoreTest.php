<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetDomainsToIgnoreTest extends \PHPUnit_Framework_TestCase {
    
    public function testDefaultDomainsToIgnore() {        
        $configuration = new Configuration();
        $this->assertEquals(array(), $configuration->getDomainsToIgnore());
    }
    
    
    public function testSetReturnsSelf() {
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setDomainsToIgnore(array()));
    }
    
    
    public function testSetGetDomainsToIgnore() {        
        $domainsToIgnore = array('foo', 'bar');
        
        $configuration = new Configuration();
        $configuration->setDomainsToIgnore($domainsToIgnore);
        $this->assertEquals($domainsToIgnore, $configuration->getDomainsToIgnore());
    }    
    
}