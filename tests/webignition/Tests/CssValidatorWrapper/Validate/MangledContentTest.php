<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class MangledContentTest extends BaseTest {
    
    private $wrapper;
    
    public function setUp() {                       
        $this->setTestFixturePath(__CLASS__, $this->getName());        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setBaseRequest($this->getHttpClient()->get());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('no-messages.txt'));
    }

    public function testMissingClosingAngleBracketOnStylesheetLink() {
        $output = $this->wrapper->validate();
        
        $this->assertFalse($output->hasException());
    }
     
}