<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use webignition\CssValidatorWrapper\SourceMap;

class SourceMapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getLocalPathDataProvider
     */
    public function testGetLocalPath(array $mappings, string $sourcePath, ?string $expectedLocalPath)
    {
        $sourceMap = new SourceMap($mappings);

        $this->assertEquals($expectedLocalPath, $sourceMap->getLocalPath($sourcePath));
    }

    public function getLocalPathDataProvider(): array
    {
        return [
            'no mappings' => [
                'mappings' => [],
                'sourcePath' => 'http://example.com/style.css',
                'expectedLocalPath' => null,
            ],
            'no matching mapping' => [
                'mappings' => [
                    'http://example.com/foo.css' => 'file:///foo.css',
                ],
                'sourcePath' => 'http://example.com/bar.css',
                'expectedLocalPath' => null,
            ],
            'has matching mapping' => [
                'mappings' => [
                    'http://example.com/foo.css' => 'file:///foo.css',
                ],
                'sourcePath' => 'http://example.com/foo.css',
                'expectedLocalPath' => 'file:///foo.css',
            ],
        ];
    }

    public function testOffsetSetInvalidOffsetType()
    {
        $sourceMap = new SourceMap();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('array key must be a string');

        $sourceMap[] = 'foo';
    }

    public function testOffsetSetInvalidValueType()
    {
        $sourceMap = new SourceMap();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('array value must be a string');

        $sourceMap['foo'] = true;
    }

    public function testOffsetGet()
    {
        $mappings = [
            'a' => 'first',
            'b' => 'second',
        ];
        $sourceMap = new SourceMap($mappings);

        $this->assertEquals('first', $sourceMap['a']);
        $this->assertEquals('second', $sourceMap['b']);
        $this->assertNull($sourceMap[1]);
    }

    public function testOffsetExists()
    {
        $mappings = [
            'a' => 'first',
            'b' => 'second',
        ];
        $sourceMap = new SourceMap($mappings);

        $this->assertTrue(isset($sourceMap['a']));
        $this->assertTrue(isset($sourceMap['b']));
        $this->assertFalse(isset($sourceMap['c']));
        $this->assertFalse(isset($sourceMap[1]));
    }

    public function testOffsetUnset()
    {
        $mappings = [
            'a' => 'first',
            'b' => 'second',
        ];
        $sourceMap = new SourceMap($mappings);

        $this->assertEquals('first', $sourceMap['a']);
        $this->assertEquals('second', $sourceMap['b']);

        unset($sourceMap['a']);

        $this->assertNull($sourceMap['a']);
        $this->assertEquals('second', $sourceMap['b']);
    }
}
