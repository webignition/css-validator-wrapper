<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Psr\Http\Message\UriInterface;
use webignition\CssValidatorWrapper\Source;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\CssValidatorWrapper\SourceMutator;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureModifier;
use webignition\WebResource\WebPage\WebPage;

class SourceMutatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider replaceStylesheetUrlsNoChangesMadeDataProvider
     */
    public function testReplaceStylesheetUrlsNoChangesMade(
        WebPage $webPage,
        SourceMap $sourceMap
    ) {
        $sourceInspector = new SourceInspector($webPage);
        $mutator = new SourceMutator($webPage, $sourceMap, $sourceInspector);
        $returnedWebPage = $mutator->replaceStylesheetUrls($sourceInspector->findStylesheetReferences());

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
            ],
        ];
    }

    /**
     * @dataProvider replaceStylesheetUrlsMutatesWebPageDataProvider
     */
    public function testReplaceStylesheetUrlsMutateWebPageFoo(
        WebPage $webPage,
        SourceMap $sourceMap,
        string $expectedWebPageContent
    ) {
        $sourceInspector = new SourceInspector($webPage);
        $mutator = new SourceMutator($webPage, $sourceMap, $sourceInspector);
        $mutatedWebPage = $mutator->replaceStylesheetUrls($sourceInspector->findStylesheetReferences());

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
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet">',
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet">',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
            ],
            'single linked stylesheet, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ]
                    )
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet">',
                    '<link' . "\n            " .
                    'href="file:' . $cssValidNoMessagePath . '"' . "\n            " .
                    'rel="stylesheet">',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
            ],
            'three linked stylesheets' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:' . $cssOnePath),
                    new Source('http://example.com/two.css', 'file:' . $cssTwoPath),
                    new Source(
                        'http://example.com/three.css?foo=bar&amp;foobar=foobar',
                        'file:' . $cssThreePath
                    ),
                ]),
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
                        '<link href="" accesskey="1">',
                        '<link href="" accesskey="2">',
                        '<link href=" ">',
                        '<link href=\'\'>',
                        '<link href=\' \'>',
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">',
                        '<link href="file:' . $cssThreePath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
                ),
            ],
            'three linked stylesheets, new lines in link elements' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        [
                            '<link href="" accesskey="2" rel="stylesheet">',
                            '<link href="/one.css" rel="stylesheet">',
                        ]
                    )
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:' . $cssOnePath),
                    new Source('http://example.com/two.css', 'file:' . $cssTwoPath),
                    new Source(
                        'http://example.com/three.css?foo=bar&amp;foobar=foobar',
                        'file:' . $cssThreePath
                    ),
                ]),
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
                        '<link href="" accesskey="1">',
                        '<link' . "\n            " . 'href=""' . "\n            " . 'accesskey="2">',
                        '<link href=" ">',
                        '<link href=\'\'>',
                        '<link href=\' \'>',
                        '<link' . "\n            " .
                        'href="file:' . $cssOnePath . '"' . "\n            " .
                        'rel="stylesheet">',
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
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet"',
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet"',
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html')
                ),
            ],
            'single malformed stylesheet, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    'http://example.com/',
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet"',
                        ]
                    )
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet"',
                    '<link' . "\n            " .
                    'href="file:' . $cssValidNoMessagePath . '"' . "\n            " .
                    'rel="stylesheet"',
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html')
                ),
            ],
        ];
    }

    /**
     * @dataProvider replaceStylesheetUrlsMutatesWebPageUnavailableStylesheetDataProvider
     */
    public function testReplaceStylesheetUrlsMutateWebPageUnavailableStylesheet(
        string $stylesheetLinkElement,
        string $expectedStylesheetLinkElement
    ) {
        $webPage = $this->createWebPage(
            'http://example.com/',
            str_replace(
                '<link href="/style.css" rel="stylesheet">',
                $stylesheetLinkElement,
                FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
            )
        );

        $sourceMap = new SourceMap([
            new Source('http://example.com/style.css'),
        ]);

        $expectedWebPageContent = str_replace(
            '<link href="/style.css" rel="stylesheet">',
            $expectedStylesheetLinkElement,
            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
        );

        $sourceInspector = new SourceInspector($webPage);
        $mutator = new SourceMutator($webPage, $sourceMap, $sourceInspector);
        $mutatedWebPage = $mutator->replaceStylesheetUrls($sourceInspector->findStylesheetReferences());

        $this->assertInstanceOf(WebPage::class, $mutatedWebPage);
        $this->assertNotSame($webPage, $mutatedWebPage);
        $this->assertEquals($expectedWebPageContent, $mutatedWebPage->getContent());
    }

    public function replaceStylesheetUrlsMutatesWebPageUnavailableStylesheetDataProvider(): array
    {
        return [
            'trailing rel="stylesheet"' => [
                'stylesheetLinkElement' => '<link href="/style.css" rel="stylesheet">',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
            ],
            'trailing rel = "stylesheet"' => [
                'stylesheetLinkElement' => '<link href="/style.css" rel = "stylesheet">',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
            ],
            'trailing rel=\'stylesheet\'"' => [
                'stylesheetLinkElement' => '<link href="/style.css" rel=\'stylesheet\'>',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
            ],
            'trailing rel = \'stylesheet\'"' => [
                'stylesheetLinkElement' => '<link href="/style.css" rel = \'stylesheet\'>',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
            ],
            'leading rel="stylesheet"' => [
                'stylesheetLinkElement' => '<link rel="stylesheet" href="/style.css">',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
            ],
            'leading rel = "stylesheet"' => [
                'stylesheetLinkElement' => '<link rel = "stylesheet" href="/style.css">',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
            ],
            'leading rel=\'stylesheet\'' => [
                'stylesheetLinkElement' => '<link rel=\'stylesheet\' href="/style.css">',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
            ],
            'leading rel = \'stylesheet\'' => [
                'stylesheetLinkElement' => '<link rel = \'stylesheet\' href="/style.css">',
                'expectedStylesheetLinkElement' => '<link href="/style.css">',
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
