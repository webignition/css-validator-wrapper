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
            'single linked stylesheet, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    str_replace(
                        '<link href="/style.css" rel="stylesheet">',
                        $this->addLineReturnsToLinkElement('<link href="/style.css" rel="stylesheet">'),
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
            'three linked stylesheets, new lines in link elements' => [
                'webPage' => $this->createWebPage(
                    $this->addLineReturnsToLinkElements(
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
                    $this->addLineReturnsToLinkElements(
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
            'single linked stylesheet, new lines in link element' => [
                'webPage' => $this->createWebPage(
                    $this->addLineReturnsToLinkElements(
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
                    $this->addLineReturnsToLinkElements(
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
                    $this->addLineReturnsToLinkElements(
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

    private function addLineReturnsToLinkElements(string $webPageContent, array $linkElements)
    {
        $replacements = [];

        foreach ($linkElements as $linkElement) {
            $replacements[] = $this->addLineReturnsToLinkElement($linkElement);
        }

        return str_replace($linkElements, $replacements, $webPageContent);
    }

    private function addLineReturnsToLinkElement(string $linkElement): string
    {
        $parts = explode(' ', $linkElement);
        $partCount = count($parts);

        $updatedLinkElement = '';

        foreach ($parts as $partIndex => $part) {
            $updatedLinkElement .= $part;

            if ($partIndex < $partCount - 1) {
                $updatedLinkElement .= "\n            ";
            }
        }

        return $updatedLinkElement;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
