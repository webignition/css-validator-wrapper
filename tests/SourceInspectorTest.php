<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Psr\Http\Message\UriInterface;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\WebResource\WebPage\WebPage;

class SourceInspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider findStylesheetUrlsDataProvider
     */
    public function testFindStylesheetUrls(WebPage $webPage, array $expectedStylesheetUrls)
    {
        $this->assertEquals($expectedStylesheetUrls, SourceInspector::findStylesheetUrls($webPage));
    }

    public function findStylesheetUrlsDataProvider()
    {
        return [
            'no linked resources' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [],
            ],
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'three linked stylesheets' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar',
                ],
            ],
            'single linked stylesheet, malformed markup' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findStylesheetUrlReferencesDataProvider
     */
    public function testFindStylesheetUrlReferences(WebPage $webPage, array $expectedStylesheetUrlReferences)
    {
        $this->assertEquals($expectedStylesheetUrlReferences, SourceInspector::findStylesheetUrlReferences($webPage));
    }

    public function findStylesheetUrlReferencesDataProvider()
    {
        return [
//            'no linked resources' => [
//                'webPage' => $this->createWebPage(
//                    FixtureLoader::load('Html/minimal-html5.html'),
//                    $this->createUri('http://example.com/')
//                ),
//                'expectedStylesheetUrlReferences' => [],
//            ],
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
            ],
//            'three linked stylesheets' => [
//                'webPage' => $this->createWebPage(
//                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
//                    $this->createUri('http://example.com/')
//                ),
//                'expectedStylesheetUrlReferences' => [
//                    'http://example.com/one.css',
//                    'http://example.com/two.css',
//                    'http://example.com/three.css?foo=bar&foobar=foobar',
//                ],
//            ],
//            'single linked stylesheet, malformed markup' => [
//                'webPage' => $this->createWebPage(
//                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
//                    $this->createUri('http://example.com/')
//                ),
//                'expectedStylesheetUrlReferences' => [
//                    'http://example.com/style.css',
//                ],
//            ],
        ];
    }

    private function createWebPage(string $content, UriInterface $uri): WebPage
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
