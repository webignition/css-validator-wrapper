<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class EnsureCssResourcesWithEncodedAmpersandsAreReplacedInModifiedOutputTest extends BaseTest {
    
    private $wrapper;
    
    public function setUp() {                       
        $this->setTestFixturePath(__CLASS__, $this->getName());        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://en.wikipedia.org/');
        $configuration->setBaseRequest($this->getHttpClient()->get());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('/CssValidatorOutput/1'));
    }

    public function testTest() {
        //$this->assertTrue(true);
        
        $output = $this->wrapper->validate();
        //var_dump($output);
//        
//        //$this->assertFalse($output->hasException());
    }
     
}