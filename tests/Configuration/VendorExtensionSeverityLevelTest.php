<?php

namespace webignition\CssValidatorWrapper\Tests\Configuration;

use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class VendorExtensionSeverityLevelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isValidDataProvider
     *
     * @param string $severityLevel
     */
    public function testIsValid(string $severityLevel)
    {
        $this->assertTrue(VendorExtensionSeverityLevel::isValid($severityLevel));
    }

    public function isValidDataProvider(): array
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
    public function testIsInvalidValid(string $flag)
    {
        $this->assertFalse(VendorExtensionSeverityLevel::isValid($flag));
    }

    public function isInvalidDataProvider(): array
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
