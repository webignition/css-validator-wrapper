<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use GuzzleHttp\Psr7\Uri;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\CssValidatorWrapper\Source;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\CssValidatorWrapper\SourceStorage;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFactory;
use webignition\WebResource\WebPage\WebPage;

class SourceStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider storeUnknownSourceExceptionDataProvider
     */
    public function testStoreUnknownSourceException(
        WebPage $webPage,
        SourceMap $sourceMap,
        string $expectedExceptionMessage
    ) {
        $sourceInspector = new SourceInspector($webPage);
        $sourceStorage = new SourceStorage();
        $stylesheetUrls = $sourceInspector->findStylesheetUrls();

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $sourceStorage->store($webPage, $sourceMap, $stylesheetUrls);
    }

    public function storeUnknownSourceExceptionDataProvider()
    {
        return [
            'single linked stylesheet' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/style.css"',
            ],
            'three linked stylesheets, empty source map' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/one.css"',
            ],
            'three linked stylesheets, first stylesheet in source map' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:/tmp/one.css'),
                ]),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/two.css"',
            ],
            'three linked stylesheets, first and stylesheets in source map' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:/tmp/one.css'),
                    new Source('http://example.com/two.css', 'file:/tmp/two.css'),
                ]),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/three.css?foo=bar&foobar=foobar"',
            ],
        ];
    }

    /**
     * @dataProvider storeSuccessDataProvider
     */
    public function testStoreSuccess(
        array $sources,
        WebPage $webPage,
        SourceMap $sourceMap,
        array $expectedStoredResources
    ) {
        foreach ($sources as $filename => $content) {
            file_put_contents($filename, $content);
        }

        $sourceInspector = new SourceInspector($webPage);
        $sourceStorage = new SourceStorage();
        $stylesheetUrls = $sourceInspector->findStylesheetUrls();

        $sourceStorage->store($webPage, $sourceMap, $stylesheetUrls);

        $sources = $sourceStorage->getSources();

        $this->assertInstanceOf(SourceMap::class, $sources);
        $this->assertEquals(count($expectedStoredResources), count($sources));

        foreach ($expectedStoredResources as $url => $expectedContent) {
            $source = $sources[$url];

            $this->assertEquals(
                $expectedContent,
                file_get_contents(preg_replace('/^file:/', '', $source->getLocalUri()))
            );
        }

        $sourceStorage->deleteAll();
    }

    public function storeSuccessDataProvider()
    {
        return [
            'no linked resources' => [
                'sources' => [],
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
                'expectedStoredResources' => [
                    'http://example.com/' => FixtureLoader::load('Html/minimal-html5.html'),
                ],
            ],
            'single linked stylesheet, unavailable' => [
                'sources' => [],
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css'),
                ]),
                'expectedStoredResources' => [
                    'http://example.com/' => FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                ],
            ],
            'single linked stylesheet, available' => [
                'sources' => [
                    '/tmp/style.css' => 'html {}',
                ],
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', '/tmp/style.css'),
                ]),
                'expectedStoredResources' => [
                    'http://example.com/' => FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    'http://example.com/style.css' => 'html {}',
                ],
            ],
            'three linked stylesheets' => [
                'sources' => [
                    '/tmp/one.css' => 'one {}',
                    '/tmp/two.css' => 'two {}',
                    '/tmp/three.css' => 'three {}',
                ],
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', '/tmp/one.css'),
                    new Source('http://example.com/two.css', '/tmp/two.css'),
                    new Source('http://example.com/three.css?foo=bar&foobar=foobar', '/tmp/three.css'),
                ]),
                'expectedStoredResources' => [
                    'http://example.com/' => FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    'http://example.com/one.css' => 'one {}',
                    'http://example.com/two.css' => 'two {}',
                    'http://example.com/three.css?foo=bar&foobar=foobar' => 'three {}',
                ],
            ],
        ];
    }
}
