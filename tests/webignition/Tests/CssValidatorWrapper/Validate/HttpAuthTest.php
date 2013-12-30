<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class HttpAuthTest extends BaseTest {
    
    public function setUp() {
        $this->setTestFixturePath(__CLASS__, $this->getName());
    }

    public function testNotSet() {
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setCssValidatorJarPath('/home/jon/tools/css-validator/2002/css-validator/css-validator.jar');
        
        $wrapper = $this->getNewCssValidatorWrapper(); 
        $wrapper->setCssValidatorRawOutput($this->getFixture('http-auth-protocol-exception.txt'));
        $wrapper->setConfiguration($configuration);
        
        /* @var $output \webignition\CssValidatorOutput\CssValidatorOutput */
        $output = $wrapper->validate();
        $this->assertTrue($output->getIsHttpAuthProtocolErrorOutput());
    }
    
}