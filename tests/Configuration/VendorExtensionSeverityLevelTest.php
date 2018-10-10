<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class VendorExtensionSeverityLevelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isValidDataProvider
     *
     * @param string $severityLevel
     */
    public function testIsValid($severityLevel)
    {
        $this->assertTrue(VendorExtensionSeverityLevel::isValid($severityLevel));
    }

    /**
     * @return array
     */
    public function isValidDataProvider()
    {
        $testData = [];

        foreach (VendorExtensionSeverityLevel::getValidValues() as $validValue) {
            $testData[] = [
                'severityLevel' => $validValue,
            ];
        }

        return $testData;
    }

    /**
     * @dataProvider isInvalidDataProvider
     *
     * @param string $flag
     */
    public function testIsInvalidValid($flag)
    {
        $this->assertFalse(VendorExtensionSeverityLevel::isValid($flag));
    }

    /**
     * @return array
     */
    public function isInvalidDataProvider()
    {
        return [
            [
                'severityLevel' => 'foo',
            ],
            [
                'severityLevel' => 'bar',
            ],
        ];
    }
}
