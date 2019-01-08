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
}
