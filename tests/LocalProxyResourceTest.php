<?php

namespace webignition\Tests\CssValidatorWrapper\Wrapper;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\LocalProxyResource;
use webignition\Tests\CssValidatorWrapper\BaseTest;
use webignition\WebResource\Exception\Exception as WebResourceException;
use webignition\GuzzleHttp\Exception\CurlException\Exception as GuzzleCurlException;

class LocalProxyResourceTest extends BaseTest
{
    /**
     * @dataProvider prepareFromContentToValidateDataProvider
     *
     * @param string $content
     * @param string $expectedLocalResourcePathExtension
     */
    public function testPrepareFromContentToValidate($content, $expectedLocalResourcePathExtension)
    {
        $localProxyResource = new LocalProxyResource(new Configuration([
            Configuration::CONFIG_KEY_CONTENT_TO_VALIDATE => $content,
        ]));

        $this->assertEmpty($localProxyResource->getConfiguration()->getUrlToValidate());

        $localProxyResource->prepare();

        $urlToValidate = $localProxyResource->getConfiguration()->getUrlToValidate();

        $this->assertRegExp(
            $this->createLocalResourcePathPattern($expectedLocalResourcePathExtension),
            $urlToValidate
        );

        $this->assertFileExists($this->getLocalPathFromFileUrlToValidate($urlToValidate));
    }

    /**
     * @return array
     */
    public function prepareFromContentToValidateDataProvider()
    {
        return [
            'css document' => [
                'content' => 'body { color: #ff0000 }',
                'expectedLocalResourcePathExtension' => 'css',
            ],
            'html document' => [
                'content' => $this->loadHtmlDocumentFixture('minimal-html5'),
                'expectedLocalResourcePathExtension' => 'html',
            ],
        ];
    }

    public function testPrepareWithNoStylesheets()
    {
        $localProxyResource = $this->createLocalProxyResource($this->loadHtmlDocumentFixture('minimal-html5'));
        $localProxyResource->prepare();

        $urlToValidate = $localProxyResource->getConfiguration()->getUrlToValidate();

        $this->assertRegExp(
            $this->createLocalResourcePathPattern('html'),
            $urlToValidate
        );

        $this->assertFileExists($this->getLocalPathFromFileUrlToValidate($urlToValidate));
    }

    /**
     * @dataProvider prepareFromHtmlDocumentWithLinkedStylesheetsDataProvider
     *
     * @param string $sourceDocument
     * @param string[] $cssHttpResponseBodies
     * @param string[] $expectedSourceCssElements
     * @param string[] $expectedPreparedCssElementPatterns
     */
    public function testPrepareWithLinkedStylesheetsReplacesCssLinks(
        $sourceDocument,
        $cssHttpResponseBodies,
        $expectedSourceCssElements,
        $expectedPreparedCssElementPatterns
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpResponseBodies);
        $localProxyResource->prepare();

        $preparedHtmlDocument = $localProxyResource->getRootWebResource()->getContent();

        $preparedHtmlDocumentCssElements = $this->getCssLinkElementsFromHtmlDocument(
            $preparedHtmlDocument
        );

        $this->assertEquals(count($expectedSourceCssElements), count($preparedHtmlDocumentCssElements));

        foreach ($expectedSourceCssElements as $expectedSourceHtmlDocumentCssElement) {
            $this->assertEquals(0, substr_count($preparedHtmlDocument, $expectedSourceHtmlDocumentCssElement));
        }

        foreach ($expectedPreparedCssElementPatterns as $index => $expectedPreparedCssElementPattern) {
            $this->assertRegExp($expectedPreparedCssElementPattern, $preparedHtmlDocumentCssElements[$index]);
        }
    }

    /**
     * @return array
     */
    public function prepareFromHtmlDocumentWithLinkedStylesheetsDataProvider()
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedSourceCssElements' => [
                    '<link href="/style.css" rel="stylesheet">',
                ],
                'expectedPreparedCssElementPatterns' => [
                    $this->createRewrittenStylesheetLinkPattern(),
                ],
            ],
            'three stylesheets' => [
                'expectedSourceCssElements' => [
                    '<link href="/one.css" rel="stylesheet">',
                    '<link href="/two.css" rel="stylesheet">',
                    '<link href="/three.css" rel="stylesheet">',
                ],
                'expectedPreparedCssElementPatterns' => [
                    $this->createRewrittenStylesheetLinkPattern(),
                    $this->createRewrittenStylesheetLinkPattern(),
                    $this->createRewrittenStylesheetLinkPattern(),
                ],
            ],
            'five stylesheets' => [
                'expectedSourceCssElements' => [
                    '<link href="/one.css" rel="stylesheet">',
                    '<link href="/two.css" rel="stylesheet">',
                    '<link href="/three.css" rel="stylesheet">',
                    '<link href="/four.css" rel="stylesheet">',
                    '<link href="/five.css" rel="stylesheet">',
                ],
                'expectedPreparedCssElementPatterns' => [
                    '<link href="about:blank" rel="stylesheet">',
                    $this->createRewrittenStylesheetLinkPattern(),
                    '<link href="about:blank" rel="stylesheet">',
                    $this->createRewrittenStylesheetLinkPattern(),
                    $this->createRewrittenStylesheetLinkPattern(),
                ],
            ],
            'base element' => [
                'expectedSourceCssElements' => [
                    '<link href="foo.css" rel="stylesheet">',
                ],
                'expectedPreparedCssElementPatterns' => [
                    $this->createRewrittenStylesheetLinkPattern(),
                ],
            ],
        ]);
    }

    /**
     * @dataProvider getWebResourceExceptionsDataProvider
     *
     * @param string $sourceDocument
     * @param string[] $cssHttpResponseBodies
     * @param bool $expectedHasWebResourceExceptions
     * @param WebResourceException[] $expectedWebResourceExceptions
     */
    public function testGetWebResourceExceptions(
        $sourceDocument,
        $cssHttpResponseBodies,
        $expectedHasWebResourceExceptions,
        $expectedWebResourceExceptions
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpResponseBodies);
        $localProxyResource->prepare();

        $webResourceExceptions = $localProxyResource->getWebResourceExceptions();

        $this->assertEquals($expectedHasWebResourceExceptions, $localProxyResource->hasWebResourceExceptions());
        $this->assertCount(count($expectedWebResourceExceptions), $webResourceExceptions);

        foreach ($webResourceExceptions as $urlHash => $webResourceException) {
            $this->assertTrue(array_key_exists($urlHash, $expectedWebResourceExceptions));
            $expectedWebResourceException = $expectedWebResourceExceptions[$urlHash];

            $this->assertEquals($expectedWebResourceException['url'], $webResourceException->getRequest()->getUrl());
            $this->assertEquals($expectedWebResourceException['code'], $webResourceException->getCode());
            $this->assertEquals($expectedWebResourceException['message'], $webResourceException->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getWebResourceExceptionsDataProvider()
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedHasWebResourceExceptions' => false,
                'expectedWebResourceExceptions' => [],
            ],
            'three stylesheets' => [
                'expectedHasWebResourceExceptions' => false,
                'expectedWebResourceExceptions' => [],
            ],
            'five stylesheets' => [
                'expectedHasWebResourceExceptions' => true,
                'expectedWebResourceExceptions' => [
                    'f60aa2b17bb65faf34062becac5cfe65' => [
                        'url' => 'http://example.com/one.css',
                        'code' => 500,
                        'message' => 'Internal Server Error'
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider getWebCurlExceptionsDataProvider
     *
     * @param string $sourceDocument
     * @param string[] $cssHttpResponseBodies
     * @param bool $expectedHasCurlExceptions
     * @param array $expectedCurlExceptions
     */
    public function testGetCurlExceptions(
        $sourceDocument,
        $cssHttpResponseBodies,
        $expectedHasCurlExceptions,
        $expectedCurlExceptions
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpResponseBodies);
        $localProxyResource->prepare();

        $curlExceptions = $localProxyResource->getCurlExceptions();

        $this->assertEquals($expectedHasCurlExceptions, $localProxyResource->hasCurlExceptions());
        $this->assertCount(count($expectedCurlExceptions), $curlExceptions);

        foreach ($curlExceptions as $urlHash => $curlExceptionWrapper) {
            $this->assertTrue(array_key_exists($urlHash, $expectedCurlExceptions));
            $expectedCurlException = $expectedCurlExceptions[$urlHash];

            /* @var GuzzleCurlException $curlException */
            $curlException = $curlExceptionWrapper['exception'];

            $this->assertEquals($expectedCurlException['url'], $curlExceptionWrapper['url']);
            $this->assertEquals($expectedCurlException['code'], $curlException->getCurlCode());
            $this->assertEquals($expectedCurlException['message'], $curlException->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getWebCurlExceptionsDataProvider()
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedHasCurlExceptions' => false,
                'expectedCurlExceptions' => [],
            ],
            'three stylesheets' => [
                'expectedHasCurlExceptions' => false,
                'expectedCurlExceptions' => [],
            ],
            'five stylesheets' => [
                'expectedHasCurlExceptions' => true,
                'expectedCurlExceptions' => [
                    '8fc6785d17f93e6d7e1e4e2d2fc44a2b' => [
                        'url' => 'http://example.com/three.css',
                        'code' => 6,
                        'message' => 'Couldn\'t resolve host. The given remote host was not resolved.',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider getWebResourceUrlFromPathDataProvider
     *
     * @param string $sourceDocument
     * @param string[] $cssHttpResponseBodies
     * @param string[] $expectedWebResourceUrls
     */
    public function testGetWebResourceUrlFromPath(
        $sourceDocument,
        $cssHttpResponseBodies,
        $expectedWebResourceUrls
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpResponseBodies);
        $localPaths = $localProxyResource->prepare();

        foreach ($localPaths as $index => $path) {
            $this->assertEquals(
                $expectedWebResourceUrls[$index],
                $localProxyResource->getWebResourceUrlFromPath($path)
            );
        }
    }

    /**
     * @return array
     */
    public function getWebResourceUrlFromPathDataProvider()
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedWebResourceUrls' => [
                    'http://example.com',
                    'http://example.com/style.css',
                ],
            ],
            'three stylesheets' => [
                'expectedWebResourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar',
                ],
            ],
            'five stylesheets' => [
                'expectedWebResourceUrls' => [
                    'http://example.com',
                    'http://example.com/two.css',
                    'http://example.com/four.css',
                    'http://example.com/five.css',
                ],
            ],
        ]);
    }

    /**
     * @dataProvider prepareFromLinkedStylesheetsDataProvider
     *
     * @param string $sourceDocument
     * @param string[] $cssHttpResponseBodies
     */
    public function testClear(
        $sourceDocument,
        $cssHttpResponseBodies
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpResponseBodies);
        $localPaths = $localProxyResource->prepare();

        foreach ($localPaths as $localPath) {
            $this->assertFileExists($localPath);
        }

        $localProxyResource->clear();

        foreach ($localPaths as $localPath) {
            $this->assertFileNotExists($localPath);
        }
    }

    /**
     * @return array
     */
    public function clearDataProvider()
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [

            ],
            'three stylesheets' => [
                'expectedWebResourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css?foo=bar&foobar=foobar',
                ],
            ],
            'five stylesheets' => [
                'expectedWebResourceUrls' => [
                    'http://example.com',
                    'http://example.com/two.css',
                    'http://example.com/four.css',
                    'http://example.com/five.css',
                ],
            ],
        ]);
    }

    /**
     * @param array $additionalTestData
     *
     * @return array
     */
    private function mergePrepareFromLinkedStylesheetsData($additionalTestData)
    {
        $prepareFromLinkedStylesheetsData = $this->prepareFromLinkedStylesheetsDataProvider();

        $testData = [];

        foreach ($prepareFromLinkedStylesheetsData as $testSetKey => $testSet) {
            if (array_key_exists($testSetKey, $additionalTestData)) {
                $testData[$testSetKey] = array_merge($testSet, $additionalTestData[$testSetKey]);
            }
        }

        return $testData;
    }

    /**
     * @return array
     */
    public function prepareFromLinkedStylesheetsDataProvider()
    {
        $internalServerErrorResponse = new Response(500);
        $curlUnableToResolveHostResponse = new ConnectException(
            'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
            new Request('GET', 'http://example.com/')
        );

        return [
            'single stylesheet' => [
                'sourceDocument' => $this->loadHtmlDocumentFixture('minimal-html5-single-stylesheet'),
                'cssHttpResponseBodies' => [
                    $this->createHttpFixture('text/css', 'body { color: #ff0000 }'),
                ],
            ],
            'three stylesheets' => [
                'sourceDocument' => $this->loadHtmlDocumentFixture('minimal-html5-three-stylesheets'),
                'cssHttpResponseBodies' => [
                    $this->createHttpFixture('text/css', 'body { color: #ff0000 }'),
                    $this->createHttpFixture('text/css', 'body { color: #00ff00 }'),
                    $this->createHttpFixture('text/css', 'body { color: #0000ff }'),
                ],
            ],
            'five stylesheets' => [
                'sourceDocument' => $this->loadHtmlDocumentFixture('minimal-html5-five-stylesheets'),
                'cssHttpResponseBodies' => [
                    $internalServerErrorResponse,
                    $this->createHttpFixture('text/css', 'body { color: #00ff00 }'),
                    $curlUnableToResolveHostResponse,
                    $this->createHttpFixture('text/css', 'body { color: #f0000f }'),
                    $this->createHttpFixture('text/css', 'body { color: #f0f0f0 }'),
                ],
            ],
            'base element' => [
                'sourceDocument' => $this->loadHtmlDocumentFixture('base-element'),
                'cssHttpResponseBodies' => [
                    $this->createHttpFixture('text/css', 'body { color: #f0f0f0 }'),
                ],
            ],
        ];
    }

    /**
     * @param string $sourceDocument
     * @param string[] $cssHttpResponseBodies
     *
     * @return LocalProxyResource
     */
    private function createLocalProxyResource($sourceDocument, $cssHttpResponseBodies = [])
    {
        $modifiedCssHttpResponseBodies = [];

        foreach ($cssHttpResponseBodies as $cssHttpResponseBody) {
            $duplicateResponse = false;

            $responseType = gettype($cssHttpResponseBody);

            if ('string' === $responseType) {
                $duplicateResponse = true;
            } elseif ('object' === $responseType) {
                if ($cssHttpResponseBody instanceof Response) {
                    $duplicateResponse = true;
                }
            }

            $modifiedCssHttpResponseBodies[] = $cssHttpResponseBody;

            if ($duplicateResponse) {
                $modifiedCssHttpResponseBodies[] = $cssHttpResponseBody;
            }
        }

        $httpClient = $this->createHttpClient(array_merge(
            [
                "HTTP/1.1 200\nContent-type: text/html",
                $this->createHttpFixture(
                    'text/html',
                    $sourceDocument
                ),
            ],
            $modifiedCssHttpResponseBodies
        ));

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com',
            Configuration::CONFIG_KEY_HTTP_CLIENT => $httpClient,
        ]);

        return new LocalProxyResource($configuration);
    }

    /**
     * @param string $htmlDocument
     *
     * @return string[]
     */
    private function getCssLinkElementsFromHtmlDocument($htmlDocument)
    {
        $htmlDocumentDOM = new \DOMDocument();
        $htmlDocumentDOM->loadHTML($htmlDocument);

        $cssLinkElements = [];

        $linkElements = $htmlDocumentDOM->getElementsByTagName('link');
        foreach ($linkElements as $linkElement) {
            /* @var $linkElement \DOMElement */
            $hasStylesheetRelAttribute = $linkElement->getAttribute('rel') == 'stylesheet';
            $hasNonEmptyHrefAttribute = !empty(trim($linkElement->getAttribute('href')));

            if ($hasStylesheetRelAttribute && $hasNonEmptyHrefAttribute) {
                $cssLinkElements[] = trim($linkElement->ownerDocument->saveHTML($linkElement));
            }
        }
        return $cssLinkElements;
    }

    /**
     * @param string $urlToValidate
     *
     * @return string
     */
    private function getLocalPathFromFileUrlToValidate($urlToValidate)
    {
        return str_replace('file:', '', $urlToValidate);
    }

    /**
     * @param string $fileType
     *
     * @return string
     */
    private function createLocalResourcePathPattern($fileType)
    {
        return '/file:' . preg_quote(sys_get_temp_dir(), '/') . '\/[a-f0-9]{32}\.' . $fileType . '/';
    }

    /**
     * @return string
     */
    private function createRewrittenStylesheetLinkPattern()
    {
        return '<link href="file:\/tmp\/[a-f0-9]{32}\.css" rel="stylesheet">';
    }
}
