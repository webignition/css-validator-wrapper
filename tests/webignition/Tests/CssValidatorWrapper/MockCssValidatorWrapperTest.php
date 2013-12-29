<?php

namespace webignition\Tests\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Wrapper as BaseCssValidatorWrapper;
use webignition\CssValidatorWrapper\Mock\Wrapper as MockCssValidatorWrapper;


/**
 * Test that we can create and use a mock CSS validator wrapper
 * for use in unit tests whereby pre-determined validator output can be
 * set and used.
 */
class MockCssValidatorWrapperTest extends \PHPUnit_Framework_TestCase {
    
    public function testMockWrapperIsInstanceOfActualWrapper() {
        $mockWrapper = new MockCssValidatorWrapper();
        $this->assertInstanceOf('\webignition\CssValidatorWrapper\Wrapper', $mockWrapper);
    }
    
}