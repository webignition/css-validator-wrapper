<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests;

use GuzzleHttp\Psr7\Uri;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMutator;
use webignition\CssValidatorWrapper\Tests\Factory\ContentTypeFactory;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFactory;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureFactory;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureModifier;
use webignition\UrlSourceMap\Source;
use webignition\UrlSourceMap\SourceMap;
use webignition\WebResource\WebPage\WebPage;

class SourceMutatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider replaceStylesheetUrlsNoChangesMadeDataProvider
     */
    public function testReplaceStylesheetUrlsNoChangesMade(WebPage $webPage, SourceMap $sourceMap)
    {
        $sourceInspector = new SourceInspector();
        $stylesheetReferences = $sourceInspector->findStylesheetReferences($webPage);

        $mutator = new SourceMutator($sourceMap);
        $returnedWebPage = $mutator->replaceStylesheetUrls($webPage, $stylesheetReferences);

        $this->assertInstanceOf(WebPage::class, $returnedWebPage);
        $this->assertSame($webPage, $returnedWebPage);
        $this->assertEquals($webPage->getContent(), $returnedWebPage->getContent());
    }

    public function replaceStylesheetUrlsNoChangesMadeDataProvider(): array
    {
        return [
            'no linked CSS resources' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap(),
            ],
        ];
    }

    /**
     * @dataProvider replaceStylesheetUrlsMutatesWebPageDataProvider
     */
    public function testReplaceStylesheetUrlsMutateWebPage(
        WebPage $webPage,
        SourceMap $sourceMap,
        string $expectedWebPageContent
    ) {
        $sourceInspector = new SourceInspector();
        $stylesheetReferences = $sourceInspector->findStylesheetReferences($webPage);

        $mutator = new SourceMutator($sourceMap);
        $mutatedWebPage = $mutator->replaceStylesheetUrls($webPage, $stylesheetReferences);

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
            'big5 document with no charset, charset supplied' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜.css">',
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html; charset=big5')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/%E6%90%9C.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => WebPageFixtureFactory::createMarkupContainingFragment(
                    '<meta charset="big5"><link rel="stylesheet" href="file:' . $cssValidNoMessagePath . '">'
                ),
            ],
            'big5 document with no charset, no charset supplied' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜.css">',
                        null,
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/j.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => WebPageFixtureFactory::createMarkupContainingFragment(
                    '<link rel="stylesheet" href="file:' . $cssValidNoMessagePath . '">'
                ),
            ],
            'single linked stylesheet, rel before href' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="/style.css">'
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => WebPageFixtureFactory::createMarkupContainingFragment(
                    '<link rel="stylesheet" href="file:' . $cssValidNoMessagePath . '">'
                ),
            ],
            'single linked stylesheet' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    new Uri('http://example.com/')
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
            'single linked stylesheet, invalid additional href attribute' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        '<link ' . 'href="/style.css" rel="stylesheet" href="/foo.css">',
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet">',
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet" href="/foo.css">',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
            ],
            'single linked stylesheet, link element triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        '<link href="/style.css" rel="stylesheet">',
                        3
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet">',
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet">' . "\n" .
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet">' . "\n" .
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet">',
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
            ],
            'single linked stylesheet, new lines in link element' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ]
                    ),
                    new Uri('http://example.com/')
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
            'single linked stylesheet, single-quoted attributes' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        "<link href='/style.css' rel='stylesheet'>",
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet">',
                    "<link href='file:" . $cssValidNoMessagePath . "' rel='stylesheet'>",
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                ),
            ],
            'two linked stylesheets' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-two-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:' . $cssOnePath),
                    new Source('http://example.com/two.css', 'file:' . $cssTwoPath),
                ]),
                'expectedWebPageContent' => str_replace(
                    [
                        '<link href="/one.css" rel="stylesheet">',
                        '<link href="/two.css" rel="stylesheet">',
                    ],
                    [
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-two-stylesheets.html')
                ),
            ],
            'two linked stylesheets, first repeated after second' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-two-stylesheets-first-repeated.html'),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:' . $cssOnePath),
                    new Source('http://example.com/two.css', 'file:' . $cssTwoPath),
                ]),
                'expectedWebPageContent' => str_replace(
                    [
                        '<link href="/one.css" rel="stylesheet">',
                        '<link href="/two.css" rel="stylesheet">',
                        '<link href="/one.css" rel="stylesheet">',
                    ],
                    [
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">',
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-two-stylesheets-first-repeated.html')
                ),
            ],
            'two linked stylesheets, first repeated after second, first is triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-two-stylesheets-first-repeated.html'),
                        '<link href="/one.css" rel="stylesheet">',
                        3
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:' . $cssOnePath),
                    new Source('http://example.com/two.css', 'file:' . $cssTwoPath),
                ]),
                'expectedWebPageContent' => str_replace(
                    [
                        '<link href="/one.css" rel="stylesheet">',
                        '<link href="/two.css" rel="stylesheet">',
                    ],
                    [
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">' . "\n" .
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">' . "\n" .
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-two-stylesheets-first-repeated.html')
                ),
            ],
            'two linked stylesheets, first repeated after second, second is triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-two-stylesheets-first-repeated.html'),
                        '<link href="/two.css" rel="stylesheet">',
                        3
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:' . $cssOnePath),
                    new Source('http://example.com/two.css', 'file:' . $cssTwoPath),
                ]),
                'expectedWebPageContent' => str_replace(
                    [
                        '<link href="/one.css" rel="stylesheet">',
                        '<link href="/two.css" rel="stylesheet">',
                    ],
                    [
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">' . "\n" .
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">' . "\n" .
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-two-stylesheets-first-repeated.html')
                ),
            ],
            'two linked stylesheets, first link element is triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-two-stylesheets.html'),
                        '<link href="/one.css" rel="stylesheet">',
                        3
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/one.css', 'file:' . $cssOnePath),
                    new Source('http://example.com/two.css', 'file:' . $cssTwoPath),
                ]),
                'expectedWebPageContent' => str_replace(
                    [
                        '<link href="/one.css" rel="stylesheet">',
                        '<link href="/two.css" rel="stylesheet">',
                    ],
                    [
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">' . "\n" .
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">' . "\n" .
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        '<link href="file:' . $cssTwoPath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-two-stylesheets.html')
                ),
            ],
            'three linked stylesheets' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
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
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        [
                            '<link href="" accesskey="2" rel="stylesheet">',
                            '<link href="/one.css" rel="stylesheet">',
                        ]
                    ),
                    new Uri('http://example.com/')
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
            'three linked stylesheets, single-quoted attributes' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        [
                            '<link href="" accesskey="1" rel="stylesheet">',
                            '<link href="/two.css" rel="stylesheet">',
                        ],
                        [
                            "<link href='' accesskey='1' rel='stylesheet'>",
                            "<link href='/two.css' rel='stylesheet'>",
                        ],
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
                    ),
                    new Uri('http://example.com/')
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
                        "<link href='' accesskey='1'>",
                        '<link href="" accesskey="2">',
                        '<link href=" ">',
                        '<link href=\'\'>',
                        '<link href=\' \'>',
                        '<link href="file:' . $cssOnePath . '" rel="stylesheet">',
                        "<link href='file:" . $cssTwoPath . "' rel='stylesheet'>",
                        '<link href="file:' . $cssThreePath . '" rel="stylesheet">',
                    ],
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
                ),
            ],
            'single malformed stylesheet' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                    new Uri('http://example.com/')
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
            'single malformed stylesheet, link element is triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                        '<link href="/style.css" rel="stylesheet"',
                        3
                    ),
                    new Uri('http://example.com/')
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/style.css', 'file:' . $cssValidNoMessagePath),
                ]),
                'expectedWebPageContent' => str_replace(
                    '<link href="/style.css" rel="stylesheet"',
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet"' . "\n" .
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet"' . "\n" .
                    '<link href="file:' . $cssValidNoMessagePath . '" rel="stylesheet"',
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html')
                ),
            ],
            'single malformed stylesheet, new lines in link element' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet"',
                        ]
                    ),
                    new Uri('http://example.com/')
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
        $webPage = WebPageFactory::create(
            str_replace(
                '<link href="/style.css" rel="stylesheet">',
                $stylesheetLinkElement,
                FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
            ),
            new Uri('http://example.com/')
        );

        $sourceMap = new SourceMap([
            new Source('http://example.com/style.css'),
        ]);

        $expectedWebPageContent = str_replace(
            '<link href="/style.css" rel="stylesheet">',
            $expectedStylesheetLinkElement,
            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
        );

        $sourceInspector = new SourceInspector();
        $stylesheetReferences = $sourceInspector->findStylesheetReferences($webPage);

        $mutator = new SourceMutator($sourceMap);
        $mutatedWebPage = $mutator->replaceStylesheetUrls($webPage, $stylesheetReferences);

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
}
