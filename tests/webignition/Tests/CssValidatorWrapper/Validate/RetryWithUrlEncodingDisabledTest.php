<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class RetryWithUrlEncodingDisabledTest extends BaseTest {
    
    private $wrapper;
    
    public function setUp() {        
        $this->setTestFixturePath(__CLASS__);        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()) . '/HttpResponses'));        
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setBaseRequest($this->getHttpClient()->get());
        $configuration->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/rootWebResource.html'));        
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);        
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('no-messages.txt'));
    }    
   

    public function testEnableRetryWithUrlEncodingDisabledHasZeroErrors() {   
        $this->wrapper->getConfiguration()->getWebResourceService()->getConfiguration()->enableRetryWithUrlEncodingDisabled();
        $output = $this->wrapper->validate();
        
        $this->assertEquals(0, $output->getErrorCount());
    }  
    
    
    public function testDisableRetryWithUrlEncodingDisabledHasOneError() {   
        $this->wrapper->getConfiguration()->getWebResourceService()->getConfiguration()->disableRetryWithUrlEncodingDisabled();
        $output = $this->wrapper->validate();
        
        $this->assertEquals(1, $output->getErrorCount());;
    }
    
    
    public function testDisableRetryWithUrlEncodingDisabledHasErrorForS1Url() {   
        $this->wrapper->getConfiguration()->getWebResourceService()->getConfiguration()->disableRetryWithUrlEncodingDisabled();
        $output = $this->wrapper->validate();
        
        $this->assertEquals(1, count($output->getErrorsByUrl('http://s1.example.com/?%3Fstyle.css=')));
    }

    
}