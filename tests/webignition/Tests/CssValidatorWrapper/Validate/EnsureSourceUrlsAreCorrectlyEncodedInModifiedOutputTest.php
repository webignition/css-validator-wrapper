<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class EnsureSourceUrlsAreCorrectlyEncodedInModifiedOutputTest extends BaseTest {

    /**
     * @var CssValidatorWrapper
     */
    private $wrapper;
    
    public function setUp() {                       
        $this->setTestFixturePath(__CLASS__, $this->getName());        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/foo"bar');
        $configuration->setHttpClient($this->getHttpClient());
        $configuration->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/rootWebResource.html'));        
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);
        
    }

    public function testDoubleQuotesAreEscaped() {
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('double-quote-in-ref-url.txt'));
        $output = $this->wrapper->validate();
        $this->assertEquals(1, $output->getErrorCount());
    }
     
}