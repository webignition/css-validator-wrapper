<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use webignition\CssValidatorWrapper\Source;
use webignition\CssValidatorWrapper\SourceMap;

class SourceMapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getByUriDataProvider
     */
    public function testGetByUri(array $sources, string $uri, ?Source $expectedSource)
    {
        $sourceMap = new SourceMap($sources);

        $this->assertEquals($expectedSource, $sourceMap->getByUri($uri));
    }

    public function getByUriDataProvider(): array
    {
        $availableSource = new Source('http://example.com/foo.css', 'file:///foo.css');
        $unavailableSource = new Source('http://example.com/404');

        return [
            'no mappings' => [
                'sources' => [],
                'sourcePath' => 'http://example.com/style.css',
                'expectedSource' => null,
            ],
            'no matching source by uri' => [
                'sources' => [
                    $availableSource,
                    $unavailableSource,
                ],
                'sourcePath' => 'http://example.com/bar.css',
                'expectedSource' => null,
            ],
            'no matching source by type' => [
                'sources' => [
                    $unavailableSource,
                ],
                'sourcePath' => 'http://example.com/bar.css',
                'expectedSource' => null,
            ],
            'has matching source' => [
                'sources' => [
                    $availableSource,
                    $unavailableSource,
                ],
                'sourcePath' => 'http://example.com/foo.css',
                'expectedSource' => $availableSource,
            ],
        ];
    }

    /**
     * @dataProvider getByLocalUriDataProvider
     */
    public function testGetSourcePath(array $sources, string $localUri, ?Source $expectedSource)
    {
        $sourceMap = new SourceMap($sources);

        $this->assertEquals($expectedSource, $sourceMap->getByLocalUri($localUri));
    }

    public function getByLocalUriDataProvider(): array
    {
        $availableSource = new Source('http://example.com/foo.css', 'file:///foo.css');
        $unavailableSource = new Source('http://example.com/404');

        return [
            'no sources' => [
                'sources' => [],
                'localUri' => 'file:///foo.css',
                'expectedSource' => null,
            ],
            'no matching source' => [
                'sources' => [
                    $availableSource,
                    $unavailableSource,
                ],
                'localUri' => 'file:///bar.css',
                'expectedSource' => null,
            ],
            'has matching source' => [
                'sources' => [
                    $availableSource,
                    $unavailableSource,
                ],
                'localUri' => 'file:///foo.css',
                'expectedSource' => $availableSource,
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
        $this->expectExceptionMessage('array value must be a Source instance');

        $sourceMap['foo'] = true;
    }

    public function testOffsetGet()
    {
        $htmlSource = new Source('http://example.com/', 'file:/tmp/example.html');
        $cssSource = new Source('http://example.com/style.css', 'file:/tmp/style.css');

        $sourceMap = new SourceMap([
            $htmlSource,
            $cssSource,
        ]);

        $this->assertEquals($htmlSource, $sourceMap['http://example.com/']);
        $this->assertEquals($cssSource, $sourceMap['http://example.com/style.css']);
        $this->assertNull($sourceMap[1]);
    }

    public function testOffsetExists()
    {
        $htmlSource = new Source('http://example.com/', 'file:/tmp/example.html');
        $cssSource = new Source('http://example.com/style.css', 'file:/tmp/style.css');

        $sourceMap = new SourceMap([
            $htmlSource,
            $cssSource,
        ]);

        $this->assertTrue(isset($sourceMap['http://example.com/']));
        $this->assertTrue(isset($sourceMap['http://example.com/style.css']));
        $this->assertFalse(isset($sourceMap['c']));
        $this->assertFalse(isset($sourceMap[1]));
    }

    public function testOffsetUnset()
    {
        $htmlSource = new Source('http://example.com/', 'file:/tmp/example.html');
        $cssSource = new Source('http://example.com/style.css', 'file:/tmp/style.css');

        $sourceMap = new SourceMap([
            $htmlSource,
            $cssSource,
        ]);

        $this->assertEquals($htmlSource, $sourceMap['http://example.com/']);
        $this->assertEquals($cssSource, $sourceMap['http://example.com/style.css']);

        unset($sourceMap['http://example.com/']);

        $this->assertNull($sourceMap['http://example.com/']);
        $this->assertEquals($cssSource, $sourceMap['http://example.com/style.css']);
    }

    public function testIterator()
    {
        $htmlSource = new Source('http://example.com/', 'file:/tmp/example.html');
        $cssSource = new Source('http://example.com/style.css', 'file:/tmp/style.css');

        $sourceMap = new SourceMap([
            $htmlSource,
            $cssSource,
        ]);

        $this->assertEquals($htmlSource, $sourceMap->current());
        $sourceMap->next();
        $this->assertEquals($cssSource, $sourceMap->current());

        $sourceMap->rewind();
        $this->assertEquals($htmlSource, $sourceMap->current());
    }

    public function testCount()
    {
        $htmlSource = new Source('http://example.com/', 'file:/tmp/example.html');
        $cssSource = new Source('http://example.com/style.css', 'file:/tmp/style.css');

        $sourceMap = new SourceMap([
            $htmlSource,
            $cssSource,
        ]);

        $this->assertEquals(2, count($sourceMap));

        unset($sourceMap['http://example.com/']);
        $this->assertEquals(1, count($sourceMap));

        unset($sourceMap['http://example.com/style.css']);
        $this->assertEquals(0, count($sourceMap));
    }
}
