<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use webignition\Tests\CssValidatorWrapper\BaseTest;
use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetSetWebResourceServiceTest extends BaseTest {
    
    public function testSetReturnsSelf() {        
        $configuration = new Configuration();
        $this->assertEquals($configuration, $configuration->setWebResourceService(new \webignition\WebResource\Service\Service()));
    }
    
}