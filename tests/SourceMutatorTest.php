<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Psr\Http\Message\UriInterface;
use webignition\CssValidatorWrapper\Source\AvailableSource;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\CssValidatorWrapper\SourceMutator;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\WebResource\WebPage\WebPage;

class SourceMutatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider replaceStylesheetUrlsNoChangesMadeDataProvider
     */
    public function testReplaceStylesheetUrlsNoChangesMade(
        WebPage $webPage,
        SourceMap $sourceMap,
        array $stylesheetReferences
    ) {
        $sourceInspector = new SourceInspector($webPage);
        $mutator = new SourceMutator($webPage, $sourceMap, $sourceInspector);
        $returnedWebPage = $mutator->replaceStylesheetUrls($stylesheetReferences);

        $this->assertInstanceOf(WebPage::class, $returnedWebPage);
        $this->assertSame($webPage, $returnedWebPage);
        $this->assertEquals($webPage->getContent(), $returnedWebPage->getContent());
    }

    public function replaceStylesheetUrlsNoChangesMadeDataProvider(): array
    {
        return [
            'no linked CSS resources' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    FixtureLoader::load('Html/minimal-html5.html')
                ),
                'sourceMap' => new SourceMap(),
                'stylesheetReferences' => []
            ],
        ];
    }

    /**
     * @dataProvider replaceStylesheetUrlsMutatesWebPageDataProvider
     */
    public function testReplaceStylesheetUrlsMutateWebPage(
        WebPage $webPage,
        SourceMap $sourceMap,
        array $stylesheetReferences,
        string $expectedWebPageContent
    ) {
        $sourceInspector = new SourceInspector($webPage);
        $mutator = new SourceMutator($webPage, $sourceMap, $sourceInspector);
        $mutatedWebPage = $mutator->replaceStylesheetUrls($stylesheetReferences);

        $this->assertInstanceOf(WebPage::class, $mutatedWebPage);
        $this->assertNotSame($webPage, $mutatedWebPage);
        $this->assertEquals($expectedWebPageContent, $mutatedWebPage->getContent());
    }

    public function replaceStylesheetUrlsMutatesWebPageDataProvider(): array
    {
        $cssValidNoMessagePath = FixtureLoader::getPath('Css/valid-no-messages.css');
        $cssOnePath = FixtureLoader::getPath('Css/one.css');
        $cssTwoPath = FixtureLoader::getPath('Css/two.css');
        $cssThreePath = FixtureLoader::getPath('Css/three.css');

        return [
            'single linked CSS resources' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
                'sourceMap' => new SourceMap([
                    new AvailableSource('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'stylesheetReferences' => [
                    '<link href="/style.css',
                ],
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet">',
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet">',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
            ],
            'three linked stylesheets' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
                ),
                'sourceMap' => new SourceMap([
                    new AvailableSource('http://example.com/one.css', 'file:' . $cssOnePath),
                    new AvailableSource('http://example.com/two.css', 'file:' . $cssTwoPath),
                    new AvailableSource(
                        'http://example.com/three.css?foo=bar&amp;foobar=foobar',
                        'file:' . $cssThreePath
                    ),
                ]),
                'stylesheetReferences' => [
                    '<link href=""',
                    "<link href=''",
                    '<link href=" "',
                    "<link href=' '",
                    '<link href="/one.css',
                    '<link href="/two.css',
                    '<link href="/three.css?foo=bar&amp;foobar=foobar',
                ],
                'expectedWebPageContent' => str_replace(
                    [
                        '<link href="" accesskey="1" rel="stylesheet">',
                        '<link href="" accesskey="2" rel="stylesheet">',
                        '<link href=" " rel="stylesheet">',
                        '<link href=\'\' rel="stylesheet">',
                        '<link href=\' \' rel="stylesheet">',
                        '<link href="/one.css" rel="stylesheet">',
                        '<link href="/two.css" rel="stylesheet">',
                        '<link href="/three.css?foo=bar&amp;foobar=foobar" rel="stylesheet">',
                    ],
                    [
                        '<link href="' . SourceMutator::EMPTY_STYLESHEET_HREF_URL . '" accesskey="1" rel="stylesheet">',
                        '<link href="' . SourceMutator::EMPTY_STYLESHEET_HREF_URL . '" accesskey="2" rel="stylesheet">',
                        '<link href="' . SourceMutator::EMPTY_STYLESHEET_HREF_URL . '" rel="stylesheet">',
                        '<link href="' . SourceMutator::EMPTY_STYLESHEET_HREF_URL . '" rel="stylesheet">',
                        '<link href="' . SourceMutator::EMPTY_STYLESHEET_HREF_URL . '" rel="stylesheet">',
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">',
                        '<link href="file:' . $cssThreePath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
                ),
            ],
            'single malformed stylesheet' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html')
                ),
                'sourceMap' => new SourceMap([
                    new AvailableSource('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'stylesheetReferences' => [
                    '<link href="/style.css',
                ],
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet"',
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet"',
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html')
                ),
            ],
        ];
    }

    private function createWebPage(string $url, string $content): WebPage
    {
        $uri = \Mockery::mock(UriInterface::class);
        $uri
            ->shouldReceive('__toString')
            ->andReturn($url);

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($content);
        $webPage = $webPage->setUri($uri);

        return $webPage;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
