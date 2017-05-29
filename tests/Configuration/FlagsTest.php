<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use webignition\CssValidatorWrapper\Configuration\Flags;

class FlagsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isValidDataProvider
     *
     * @param string $flag
     */
    public function testIsValid($flag)
    {
        $this->assertTrue(Flags::isValid($flag));
    }

    /**
     * @return array
     */
    public function isValidDataProvider()
    {
        $testData = [];

        foreach (Flags::getValidValues() as $validValue) {
            $testData[] = [
                'flag' => $validValue,
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
        $this->assertFalse(Flags::isValid($flag));
    }

    /**
     * @return array
     */
    public function isInvalidDataProvider()
    {
        return [
            [
                'flag' => 'foo',
            ],
            [
                'flag' => 'bar',
            ],
        ];
    }
}
