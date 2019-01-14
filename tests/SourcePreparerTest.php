<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Psr\Http\Message\UriInterface;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\CssValidatorWrapper\ResourceStorage;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\CssValidatorWrapper\SourcePreparer;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResourceInterfaces\WebPageInterface;

class SourcePreparerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider prepareUnknownSourceExceptionDataProvider
     */
    public function testPrepareUnknownSourceException(
        WebPage $webPage,
        SourceMap $sourceMap,
        string $expectedExceptionMessage
    ) {
        $sourceInspector = new SourceInspector($webPage);
        $preparer = new SourcePreparer($webPage, $sourceMap, $sourceInspector);

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $preparer->prepare();
    }

    public function prepareUnknownSourceExceptionDataProvider()
    {
        return [
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/style.css"',
            ],
            'three linked stylesheets, empty source map' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/one.css"',
            ],
            'three linked stylesheets, first stylesheet in source map' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    'http://example.com/one.css' => 'file:/tmp/one.css',
                ]),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/two.css"',
            ],
            'three linked stylesheets, first and stylesheets in source map' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    'http://example.com/one.css' => 'file:/tmp/one.css',
                    'http://example.com/two.css' => 'file:/tmp/two.css',
                ]),
                'expectedExceptionMessage' => 'Unknown source "http://example.com/three.css?foo=bar&foobar=foobar"',
            ],
        ];
    }

    /**
     * @dataProvider prepareSuccessDataProvider
     */
    public function testPrepareSuccess(
        array $sources,
        WebPage $webPage,
        SourceMap $sourceMap,
        array $expectedStoredResources
    ) {
        foreach ($sources as $filename => $content) {
            file_put_contents($filename, $content);
        }

        $sourceInspector = new SourceInspector($webPage);
        $preparer = new SourcePreparer($webPage, $sourceMap, $sourceInspector);
        $resourceStorage = $preparer->prepare();

        $this->assertInstanceOf(ResourceStorage::class, $resourceStorage);
        $this->assertEquals(count($expectedStoredResources), count($resourceStorage->getPaths()));

        foreach ($expectedStoredResources as $url => $expectedContent) {
            $path = $resourceStorage->getPath($url);

            $this->assertIsString($path);
            $this->assertEquals($expectedContent, file_get_contents($path));
        }

        $resourceStorage->deleteAll();
    }

    public function prepareSuccessDataProvider()
    {
        return [
            'no linked resources' => [
                'sources' => [],
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
                'expectedStoredResources' => [
                    'http://example.com/' => FixtureLoader::load('Html/minimal-html5.html'),
                ],
            ],
            'single linked stylesheet' => [
                'sources' => [
                    '/tmp/style.css' => 'html {}',
                ],
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    'http://example.com/style.css' => '/tmp/style.css',
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
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    'http://example.com/one.css' => '/tmp/one.css',
                    'http://example.com/two.css' => '/tmp/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar' => '/tmp/three.css',
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

    private function createWebPage(string $content, UriInterface $uri): WebPageInterface
    {
        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($content);
        $webPage = $webPage->setUri($uri);

        return $webPage;
    }

    private function createUri(string $url): UriInterface
    {
        $uri = \Mockery::mock(UriInterface::class);
        $uri
            ->shouldReceive('__toString')
            ->andReturn($url);

        return $uri;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
