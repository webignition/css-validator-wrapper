<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Psr\Http\Message\UriInterface;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureModifier;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebPage\ContentEncodingValidator;
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
            'single linked stylesheet, invalid additional href attributes are ignored' => [
                'webPage' => $this->createWebPage(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        '<link '.'href="/style.css" rel="stylesheet" href="/foo.css">',
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet, link element triplicated' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        '<link href="/style.css" rel="stylesheet">',
                        3
                    ),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ]
                    ),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet, new lines in link element, link element triplicated' => [
                'webPage' => $this->createWebPage(
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
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/style.css',
                ],
            ],
            'single linked stylesheet, single-quoted attributes' => [
                'webPage' => $this->createWebPage(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        "<link href='/style.css' rel='stylesheet'>",
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
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
            'three linked stylesheets, invalid additional href attributes are ignored' => [
                'webPage' => $this->createWebPage(
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
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar',
                ],
            ],
            'three linked stylesheets, new lines in link elements' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        [
                            '<link href="" accesskey="2" rel="stylesheet">',
                            '<link href="/one.css" rel="stylesheet">',
                        ]
                    ),
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
            'single linked stylesheet, malformed markup, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet"',
                        ]
                    ),
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
        $sourceInspector = new SourceInspector($webPage);

        $this->assertEquals($expectedStylesheetUrlReferences, $sourceInspector->findStylesheetReferences());
    }

    public function findStylesheetUrlReferencesDataProvider()
    {
        return [
            'no linked resources' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [],
            ],
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
            ],
            'single linked stylesheet, invalid additional href attributes are ignored' => [
                'webPage' => $this->createWebPage(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        '<link '.'href="/style.css" rel="stylesheet" href="/foo.css">',
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
            ],
            'single linked stylesheet, link element triplicated' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        '<link href="/style.css" rel="stylesheet">',
                        3
                    ),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
            ],
            'single linked stylesheet, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ]
                    ),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link' . "\n            " . 'href="/style.css',
                ],
            ],
            'single linked stylesheet, new lines in link element, link element triplicated' => [
                'webPage' => $this->createWebPage(
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
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link' . "\n            " . 'href="/style.css',
                ],
            ],
            'single linked stylesheet, single-quoted attributes' => [
                'webPage' => $this->createWebPage(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        "<link href='/style.css' rel='stylesheet'>",
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    "<link href='/style.css",
                ],
            ],
            'three linked stylesheets' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
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
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        [
                            '<link href="" accesskey="2" rel="stylesheet">',
                            '<link href="/one.css" rel="stylesheet">',
                        ]
                    ),
                    $this->createUri('http://example.com/')
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
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'expectedStylesheetUrlReferences' => [
                    '<link href="/style.css',
                ],
            ],
            'single linked stylesheet, malformed markup, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet"',
                        ]
                    ),
                    $this->createUri('http://example.com/')
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
                'webPage' => $this->createWebPage(
                    '<!doctype html><html><head><meta charset=utf-8></head></html>',
                    $this->createUri('http://example.com/')
                ),
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [],
            ],
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                    $this->createUri('http://example.com/')
                ),
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link href="/style.css" rel="stylesheet',
                ],
            ],
            'single linked stylesheet, invalid additional href attributes' => [
                'webPage' => $this->createWebPage(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        '<link '.'href="/style.css" rel="stylesheet" href="/foo.css">',
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    $this->createUri('http://example.com/')
                ),
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link href="/style.css" rel="stylesheet',
                ],
            ],
            'single linked stylesheet, link element triplicated' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        '<link href="/style.css" rel="stylesheet">',
                        3
                    ),
                    $this->createUri('http://example.com/')
                ),
                'reference' => '<link href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link href="/style.css" rel="stylesheet',
                    '">' . "\n" . '<link href="/style.css" rel="stylesheet',
                    '">' . "\n" . '<link href="/style.css" rel="stylesheet',
                ],
            ],
            'single linked stylesheet, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ]
                    ),
                    $this->createUri('http://example.com/')
                ),
                'reference' => '<link' . "\n            " . 'href="/style.css',
                'expectedStylesheetReferenceFragments' => [
                    '<link' . "\n            " . 'href="/style.css"' . "\n            " . 'rel="stylesheet',
                ],
            ],
            'single linked stylesheet, single-quoted attributes' => [
                'webPage' => $this->createWebPage(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        "<link href='/style.css' rel='stylesheet'>",
                        FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
                    ),
                    $this->createUri('http://example.com/')
                ),
                'reference' => "<link href='/style.css",
                'expectedStylesheetReferenceFragments' => [
                    "<link href='/style.css' rel='stylesheet",
                ],
            ],
            'three linked stylesheets (1)' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'reference' => '<link href=""',
                'expectedStylesheetUrlReferences' => [
                    '<link href="" accesskey="1" rel="stylesheet',
                    '">'."\n".'        <link href="" accesskey="2" rel="stylesheet',
                ],
            ],
            'three linked stylesheets (1), new lines in link element' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::addLineReturnsToLinkElements(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        [
                            '<link href="" accesskey="1" rel="stylesheet">',
                        ]
                    ),
                    $this->createUri('http://example.com/')
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
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'reference' => "<link href=''",
                'expectedStylesheetUrlReferences' => [
                    '<link href=\'\' rel="stylesheet',
                ],
            ],
            'three linked stylesheets (3)' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'reference' => "<link href=' '",
                'expectedStylesheetUrlReferences' => [
                    '<link href=\' \' rel="stylesheet',
                ],
            ],
            'three linked stylesheets (4)' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                    $this->createUri('http://example.com/')
                ),
                'reference' => '<link href="/two.css"',
                'expectedStylesheetUrlReferences' => [
                    '<link href="/two.css" rel="stylesheet',
                ],
            ],
            'three linked stylesheets (4), link element is triplicated' => [
                'webPage' => $this->createWebPage(
                    WebPageFixtureModifier::repeatContent(
                        FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                        '<link href="/two.css" rel="stylesheet">',
                        3
                    ),
                    $this->createUri('http://example.com/')
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

    private function createWebPage(
        string $content,
        UriInterface $uri,
        ?InternetMediaTypeInterface $contentType = null
    ): WebPage {
        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($content, $contentType);
        $webPage = $webPage->setUri($uri);

        $contentEncodingValidator = new ContentEncodingValidator();
        if (false === $contentEncodingValidator->isValid($webPage)) {
            $webPage = $contentEncodingValidator->convertToUtf8($webPage);
        }

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
