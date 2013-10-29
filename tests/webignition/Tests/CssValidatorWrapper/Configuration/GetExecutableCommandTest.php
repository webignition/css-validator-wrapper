<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use \webignition\CssValidatorWrapper\Configuration\Configuration;

class GetExecutableCommandTest extends \PHPUnit_Framework_TestCase {
    
    public function testWithDefaultValuesThrowsInvalidArgumentException() {        
        $this->setExpectedException('InvalidArgumentException');
        
        $configuration = new Configuration();
        $configuration->getExecutableCommand();
    } 
    
    public function testWithDefaultValuesAndUrlToTest() {                
        $configuration = new Configuration();
        
        $this->assertEquals(
            'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
             $configuration->setUrlToValidate('http://example.com/')->getExecutableCommand()
        );
    }    
    
}