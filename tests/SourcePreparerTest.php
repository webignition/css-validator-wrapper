<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Psr\Http\Message\UriInterface;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
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
        $preparer = new SourcePreparer();

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $preparer->prepare($webPage, $sourceMap);
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
     * @dataProvider prepareDataProvider
     */
    public function testPrepare(WebPage $webPage, SourceMap $sourceMap, string $expectedPreparedContent)
    {
        $preparer = new SourcePreparer();
        $preparer->prepare($webPage, $sourceMap);

        $this->assertEquals($expectedPreparedContent, $webPage->getContent());
    }

    public function prepareDataProvider()
    {
        return [
            'no linked resources' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
                'expectedPreparedContent' => FixtureLoader::load('Html/minimal-html5.html'),
            ],
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    'http://example.com/style.css' => 'file:/tmp/style.css',
                ]),
                'expectedPreparedContent' => FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
            ],
            'three linked stylesheets' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    'http://example.com/one.css' => 'file:/tmp/one.css',
                    'http://example.com/two.css' => 'file:/tmp/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar' => 'file:/tmp/three.css',
                ]),
                'expectedPreparedContent' => FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
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
