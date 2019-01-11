<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Psr\Http\Message\UriInterface;
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
        $mutator = new SourceMutator();
        $returnedWebPage = $mutator->replaceStylesheetUrls($webPage, $sourceMap, $stylesheetReferences);

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
     * @dataProvider replaceStylesheetUrlsDataProvider
     */
    public function testReplaceStylesheetUrls(
        WebPage $webPage,
        SourceMap $sourceMap,
        array $stylesheetReferences,
        string $expectedWebPageContent
    ) {
        $mutator = new SourceMutator();
        $mutatedWebPage = $mutator->replaceStylesheetUrls($webPage, $sourceMap, $stylesheetReferences);

        $this->assertInstanceOf(WebPage::class, $mutatedWebPage);
        $this->assertNotSame($webPage, $mutatedWebPage);
        $this->assertEquals($expectedWebPageContent, $mutatedWebPage->getContent());
    }

    public function replaceStylesheetUrlsDataProvider(): array
    {
        return [
            'single linked CSS resources' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
                'sourceMap' => new SourceMap([
                    'http://example.com/style.css' => FixtureLoader::getPath('Css/valid-no-messages.css'),
                ]),
                'stylesheetReferences' => [
                    '<link href="/style.css',
                ],
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet">',
                    '<link href="file:' . FixtureLoader::getPath('Css/valid-no-messages.css') . '" rel="stylesheet">',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
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
