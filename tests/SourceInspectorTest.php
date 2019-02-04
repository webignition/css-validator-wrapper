<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use GuzzleHttp\Psr7\Uri;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\Tests\Factory\ContentTypeFactory;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFactory;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureFactory;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureModifier;
use webignition\WebResource\WebPage\WebPage;

class SourceInspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider findStylesheetUrlsDataProvider
     */
    public function testFindStylesheetUrls(WebPage $webPage, array $expectedStylesheetUrls)
    {
        $sourceInspector = new SourceInspector($webPage);

        $this->assertEquals($expectedStylesheetUrls, $sourceInspector->findStylesheetUrls());
    }

    public function findStylesheetUrlsDataProvider()
    {
        return [
            'big5 document with no charset, charset supplied' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜">',
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html; charset=big5')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/' . rawurlencode('搜'),
                ],
            ],
            'big5 document with no charset, no charset supplied' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜">',
                        null,
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/j',
                ],
            ],
            'no linked resources' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [],
            ],
            'no linked resources, no document-level charset (utf-8)' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        '<meta charset=utf-8>',
                        '',
                        FixtureLoader::load('Html/minimal-html5.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [],
            ],
            'single linked stylesheet, rel before href' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="/style.css">'
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet, invalid additional href attributes are ignored' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        '<link '.'href="/style.css" rel="stylesheet" href="/foo.css">',
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
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
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
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
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet, new lines in link element, link element triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        WebPageFixtureModifier::repeatContent(
                            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                            '<link href="/style.css" rel="stylesheet">',
                            3
                        ),
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ]
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
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
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'three linked stylesheets' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar',
                ],
            ],
            'three linked stylesheets, invalid additional href attributes are ignored' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        [
                            '<link href="/one.css" rel="stylesheet">',
                            '<link href="/two.css" rel="stylesheet">',
                        ],
                        [
                            '<link '.'href="/one.css" href="/foo.css" rel="stylesheet">',
                            '<link '.'href="/two.css" rel="stylesheet" href="/bar.css">',
                        ],
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar',
                ],
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
                'expectedStylesheetUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar',
                ],
            ],
            'single linked stylesheet, malformed markup' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet, malformed markup, new lines in link element' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet"',
                        ]
                    ),
                    new Uri('http://example.com/')
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
        $sourceInspector = new SourceInspector($webPage);

        $this->assertEquals($expectedStylesheetUrlReferences, $sourceInspector->findStylesheetReferences());
    }

    public function findStylesheetUrlReferencesDataProvider()
    {
        return [
            'big5 document with no charset, charset supplied' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜">',
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html; charset=big5')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link rel="stylesheet" href="搜'
                ],
            ],
            'big5 document with no charset, no charset supplied' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜">',
                        null,
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link rel="stylesheet" href="j',
                ],
            ],
            'no linked resources' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [],
            ],
            'single linked stylesheet, rel before href' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="/style.css">'
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link rel="stylesheet" href="/style.css',
                ],
            ],
            'no linked resources, no document-level charset' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        '<meta charset=utf-8>',
                        '',
                        FixtureLoader::load('Html/minimal-html5.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [],
            ],
            'single linked stylesheet' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
            ],
            'single linked stylesheet, invalid additional href attributes are ignored' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        '<link '.'href="/style.css" rel="stylesheet" href="/foo.css">',
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
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
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
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
                'expectedStylesheetUrlReferences' => [
                    '<link' . "\n            " . 'href="/style.css',
                ],
            ],
            'single linked stylesheet, new lines in link element, link element triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        WebPageFixtureModifier::repeatContent(
                            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                            '<link href="/style.css" rel="stylesheet">',
                            3
                        ),
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ]
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link' . "\n            " . 'href="/style.css',
                ],
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
                'expectedStylesheetUrlReferences' => [
                    "<link href='/style.css",
                ],
            ],
            'three linked stylesheets' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href=""',
                    "<link href=''",
                    '<link href=" "',
                    "<link href=' '",
                    '<link href="/one.css',
                    '<link href="/two.css',
                    '<link href="/three.css?foo=bar&amp;foobar=foobar'
                ],
            ],
            'three linked stylesheets, new lines in link element' => [
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
                'expectedStylesheetUrlReferences' => [
                    '<link href=""',
                    '<link' . "\n            " . 'href=""',
                    "<link href=''",
                    '<link href=" "',
                    "<link href=' '",
                    '<link' . "\n            " . 'href="/one.css',
                    '<link href="/two.css',
                    '<link href="/three.css?foo=bar&amp;foobar=foobar'
                ],
            ],
            'single linked stylesheet, malformed markup' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
            ],
            'single linked stylesheet, malformed markup, new lines in link element' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet"',
                        ]
                    ),
                    new Uri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link' . "\n            " . 'href="/style.css',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findStylesheetReferenceFragmentsDataProvider
     */
    public function testFindStylesheetReferenceFragments(
        WebPage $webPage,
        string $reference,
        array $expectedStylesheetUrlReferences
    ) {
        $sourceInspector = new SourceInspector($webPage);

        $this->assertEquals(
            $expectedStylesheetUrlReferences,
            $sourceInspector->findStylesheetReferenceFragments($reference)
        );
    }

    public function findStylesheetReferenceFragmentsDataProvider()
    {
        return [
            'reference not present in content' => [
                'webPage' => WebPageFactory::create(
                    '<!doctype html><html><head><meta charset=utf-8></head></html>',
                    new Uri('http://example.com/')
                ),
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [],
            ],
            'big5 document with no charset, charset supplied, href before rel' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link href="搜" rel="stylesheet">',
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html; charset=big5')
                ),
                'reference' => '<link href="搜',
                'expectedStylesheetReferenceFragments' => [
                    '<link href="搜" rel="stylesheet',
                ],
            ],
            'big5 document with no charset, charset supplied, rel before href' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜">',
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html; charset=big5')
                ),
                'reference' => '<link rel="stylesheet" href="搜',
                'expectedStylesheetReferenceFragments' => [
                    '<link rel="stylesheet" href="搜',
                ],
            ],
            'big5 document with no charset, no charset supplied' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="搜">',
                        null,
                        'big5'
                    ),
                    new Uri('http://example.com/'),
                    ContentTypeFactory::createFromString('text/html')
                ),
                'reference' => '<link rel="stylesheet" href="j',
                'expectedStylesheetReferenceFragments' => [
                    '<link rel="stylesheet" href="j',
                ],
            ],
            'single linked stylesheet, rel before href' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureFactory::createMarkupContainingFragment(
                        '<link rel="stylesheet" href="/style.css">'
                    ),
                    new Uri('http://example.com/')
                ),
                'reference' => '<link rel="stylesheet" href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link rel="stylesheet" href="/style.css',
                ],
            ],
            'single linked stylesheet' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    new Uri('http://example.com/')
                ),
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link href="/style.css" rel="stylesheet',
                ],
            ],
            'single linked stylesheet, invalid additional href attributes' => [
                'webPage' => WebPageFactory::create(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        '<link '.'href="/style.css" rel="stylesheet" href="/foo.css">',
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    new Uri('http://example.com/')
                ),
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link href="/style.css" rel="stylesheet',
                ],
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
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link href="/style.css" rel="stylesheet',
                    '">' . "\n" . '<link href="/style.css" rel="stylesheet',
                    '">' . "\n" . '<link href="/style.css" rel="stylesheet',
                ],
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
                'reference' => '<link' . "\n            " . 'href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link' . "\n            " . 'href="/style.css"' . "\n            " . 'rel="stylesheet',
                ],
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
                'reference' => "<link href='/style.css",
                'expectedStylesheetReferenceFragments' => [
                    "<link href='/style.css' rel='stylesheet",
                ],
            ],
            'three linked stylesheets (1)' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'reference' => '<link href=""',
                'expectedStylesheetUrlReferences' => [
                    '<link href="" accesskey="1" rel="stylesheet',
                    '">'."\n".'        <link href="" accesskey="2" rel="stylesheet',
                ],
            ],
            'three linked stylesheets (1), new lines in link element' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        [
                            '<link href="" accesskey="1" rel="stylesheet">',
                        ]
                    ),
                    new Uri('http://example.com/')
                ),
                'reference' => '<link' . "\n            " . 'href=""',
                'expectedStylesheetUrlReferences' => [
                    '<link' . "\n            " .
                    'href=""' . "\n            " .
                    'accesskey="1"' . "\n            " .
                    'rel="stylesheet',
                ],
            ],
            'three linked stylesheets (2)' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'reference' => "<link href=''",
                'expectedStylesheetUrlReferences' => [
                    '<link href=\'\' rel="stylesheet',
                ],
            ],
            'three linked stylesheets (3)' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'reference' => "<link href=' '",
                'expectedStylesheetUrlReferences' => [
                    '<link href=\' \' rel="stylesheet',
                ],
            ],
            'three linked stylesheets (4)' => [
                'webPage' => WebPageFactory::create(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    new Uri('http://example.com/')
                ),
                'reference' => '<link href="/two.css"',
                'expectedStylesheetUrlReferences' => [
                    '<link href="/two.css" rel="stylesheet',
                ],
            ],
            'three linked stylesheets (4), link element is triplicated' => [
                'webPage' => WebPageFactory::create(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        '<link href="/two.css" rel="stylesheet">',
                        3
                    ),
                    new Uri('http://example.com/')
                ),
                'reference' => '<link href="/two.css"',
                'expectedStylesheetUrlReferences' => [
                    '<link href="/two.css" rel="stylesheet',
                    '">' . "\n" . '<link href="/two.css" rel="stylesheet',
                    '">' . "\n" . '<link href="/two.css" rel="stylesheet',
                ],
            ],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
