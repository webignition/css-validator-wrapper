<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class DomainsToIgnoreTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }

    public function testNotSet() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('domains-to-ignore.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(9, $output->getErrorCount());
    }        
    
    public function testOneDomainOfThreeIgnored() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setDomainsToIgnore(array(
            'one.cdn.example.com'
        ));
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('domains-to-ignore.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(6, $output->getErrorCount());
    }     
    
    
    public function testTwoDomainsOfThreeIgnored() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setDomainsToIgnore(array(
            'one.cdn.example.com',
            'two.cdn.example.com'
        ));
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('domains-to-ignore.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(3, $output->getErrorCount());
    }
    
    
    public function testThreeDomainsOfThreeIgnored() {        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setDomainsToIgnore(array(
            'one.cdn.example.com',
            'two.cdn.example.com',
            'example.com'
        ));
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setConfiguration($configuration);
        $wrapper->setCssValidatorRawOutput($this->getFixture('domains-to-ignore.txt'));
        
        $output = $wrapper->validate();
        $this->assertEquals(0, $output->getErrorCount());
    }
    
}