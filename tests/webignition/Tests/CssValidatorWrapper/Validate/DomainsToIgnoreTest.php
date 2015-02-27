<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class DomainsToIgnoreTest extends BaseTest {

    /**
     * @var CssValidatorWrapper
     */
    private $wrapper;
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__);        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setHttpClient($this->getHttpClient());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('domains-to-ignore.txt'));
    }

    public function testNotSet() {      
        $this->assertEquals(9, $this->wrapper->validate()->getErrorCount());
    }        
    
    public function testOneDomainOfThreeIgnored() {
        $this->wrapper->getConfiguration()->setDomainsToIgnore(array(
            'one.cdn.example.com'
        ));
        
        $this->assertEquals(6, $this->wrapper->validate()->getErrorCount());
    }     
    
    
    public function testTwoDomainsOfThreeIgnored() {
        $this->wrapper->getConfiguration()->setDomainsToIgnore(array(
            'one.cdn.example.com',
            'two.cdn.example.com'
        ));        

        $this->assertEquals(3, $this->wrapper->validate()->getErrorCount());
    }
    
    
    public function testThreeDomainsOfThreeIgnored() {
        $this->wrapper->getConfiguration()->setDomainsToIgnore(array(
            'one.cdn.example.com',
            'two.cdn.example.com',
            'example.com'
        ));        

        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());        
    }
    
}