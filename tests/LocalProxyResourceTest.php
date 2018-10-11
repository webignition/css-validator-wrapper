<?php

namespace webignition\CssValidatorWrapper\Tests;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\LocalProxyResource;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\ResponseFactory;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;

class LocalProxyResourceTest extends AbstractBaseTest
{
    const REWRITTEN_STYLESHEET_LINK_PATTERN = '<link href="file:\/tmp\/[a-f0-9]{32}\.css" rel="stylesheet">';

    /**
     * @dataProvider prepareFromContentToValidateDataProvider
     *
     * @param string $content
     * @param string $expectedLocalResourcePathExtension
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testPrepareFromContentToValidate(string $content, string $expectedLocalResourcePathExtension)
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

    public function prepareFromContentToValidateDataProvider(): array
    {
        return [
            'css document' => [
                'content' => 'body { color: #ff0000 }',
                'expectedLocalResourcePathExtension' => 'css',
            ],
            'html document' => [
                'content' => FixtureLoader::load('Html/minimal-html5.html'),
                'expectedLocalResourcePathExtension' => 'html',
            ],
        ];
    }

    /**
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     * @throws UnparseableContentTypeException
     */
    public function testPrepareWithNoStylesheets()
    {
        $localProxyResource = $this->createLocalProxyResource(FixtureLoader::load('Html/minimal-html5.html'));

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
     * @param array $cssHttpFixtures
     * @param string[] $expectedSourceCssElements
     * @param string[] $expectedPreparedCssElementPatterns
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     * @throws UnparseableContentTypeException
     */
    public function testPrepareWithLinkedStylesheetsReplacesCssLinks(
        string $sourceDocument,
        array $cssHttpFixtures,
        array $expectedSourceCssElements,
        array $expectedPreparedCssElementPatterns
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpFixtures);

        $localProxyResource->prepare();

        $preparedHtmlDocument = $localProxyResource->getConfiguration()->getContentToValidate();

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

    public function prepareFromHtmlDocumentWithLinkedStylesheetsDataProvider(): array
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedSourceCssElements' => [
                    '<link href="/style.css" rel="stylesheet">',
                ],
                'expectedPreparedCssElementPatterns' => [
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
                ],
            ],
            'three stylesheets' => [
                'expectedSourceCssElements' => [
                    '<link href="/one.css" rel="stylesheet">',
                    '<link href="/two.css" rel="stylesheet">',
                    '<link href="/three.css" rel="stylesheet">',
                ],
                'expectedPreparedCssElementPatterns' => [
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
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
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
                    '<link href="about:blank" rel="stylesheet">',
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
                ],
            ],
            'base element' => [
                'expectedSourceCssElements' => [
                    '<link href="foo.css" rel="stylesheet">',
                ],
                'expectedPreparedCssElementPatterns' => [
                    self::REWRITTEN_STYLESHEET_LINK_PATTERN,
                ],
            ],
        ]);
    }

    /**
     * @dataProvider getHttpExceptionsDataProvider
     *
     * @param string $sourceDocument
     * @param array $cssHttpFixtures
     * @param HttpException[] $expectedHttpExceptions
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     * @throws UnparseableContentTypeException
     */
    public function testGetHttpExceptions(
        string $sourceDocument,
        array $cssHttpFixtures,
        array $expectedHttpExceptions
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpFixtures);
        $localProxyResource->prepare();

        $httpExceptions = $localProxyResource->getHttpExceptions();

        $this->assertCount(count($expectedHttpExceptions), $httpExceptions);

        foreach ($httpExceptions as $urlHash => $httpException) {
            $this->assertTrue(array_key_exists($urlHash, $expectedHttpExceptions));
            $expectedWebResourceException = $expectedHttpExceptions[$urlHash];

            $this->assertEquals($expectedWebResourceException['url'], (string)$httpException->getRequest()->getUri());
            $this->assertEquals($expectedWebResourceException['code'], $httpException->getCode());
            $this->assertEquals($expectedWebResourceException['message'], $httpException->getMessage());
        }
    }

    public function getHttpExceptionsDataProvider(): array
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedWebResourceExceptions' => [],
            ],
            'three stylesheets' => [
                'expectedWebResourceExceptions' => [],
            ],
            'five stylesheets' => [
                'expectedHttpExceptions' => [
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
     * @dataProvider getTransportExceptionsDataProvider
     *
     * @param string $sourceDocument
     * @param array $cssHttpFixtures
     * @param array $expectedTransportExceptions
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     * @throws UnparseableContentTypeException
     */
    public function testGetTransportExceptions(
        string $sourceDocument,
        array $cssHttpFixtures,
        array $expectedTransportExceptions
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpFixtures);
        $localProxyResource->prepare();

        $transportExceptions = $localProxyResource->getTransportExceptions();

        $this->assertCount(count($expectedTransportExceptions), $transportExceptions);

        foreach ($transportExceptions as $urlHash => $transportException) {
            $this->assertTrue(array_key_exists($urlHash, $expectedTransportExceptions));

            $expectedExceptionData = $expectedTransportExceptions[$urlHash];

            $this->assertEquals($expectedExceptionData['url'], (string)$transportException->getRequest()->getUri());
            $this->assertEquals($expectedExceptionData['code'], $transportException->getTransportErrorCode());
            $this->assertEquals($expectedExceptionData['message'], $transportException->getMessage());
        }
    }

    public function getTransportExceptionsDataProvider(): array
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedTransportExceptions' => [],
            ],
            'three stylesheets' => [
                'expectedTransportExceptions' => [],
            ],
            'five stylesheets' => [
                'expectedTransportExceptions' => [
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
     * @dataProvider getInvalidResponseContentTypeExceptionsDataProvider
     *
     * @param string $sourceDocument
     * @param array $cssHttpFixtures
     * @param array $expectedInvalidResponseContentTypeExceptions
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     * @throws UnparseableContentTypeException
     */
    public function testGetInvalidResponseContentTypeExceptions(
        string $sourceDocument,
        array $cssHttpFixtures,
        array $expectedInvalidResponseContentTypeExceptions
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpFixtures);
        $localProxyResource->prepare();

        $invalidResponseContentTypeExceptions = $localProxyResource->getInvalidResponseContentTypeExceptions();

        $this->assertCount(count($expectedInvalidResponseContentTypeExceptions), $invalidResponseContentTypeExceptions);

        foreach ($invalidResponseContentTypeExceptions as $urlHash => $invalidResponseContentTypeException) {
            $this->assertTrue(array_key_exists($urlHash, $expectedInvalidResponseContentTypeExceptions));

            $expectedExceptionData = $expectedInvalidResponseContentTypeExceptions[$urlHash];

            $this->assertEquals(
                $expectedExceptionData['url'],
                (string)$invalidResponseContentTypeException->getRequest()->getUri()
            );
            $this->assertEquals($expectedExceptionData['message'], $invalidResponseContentTypeException->getMessage());
            $this->assertEquals(
                $expectedExceptionData['contentTypeString'],
                (string)$invalidResponseContentTypeException->getContentType()
            );
        }
    }

    public function getInvalidResponseContentTypeExceptionsDataProvider(): array
    {
        return $this->mergePrepareFromLinkedStylesheetsData([
            'single stylesheet' => [
                'expectedInvalidResponseContentTypeExceptions' => [],
            ],
            'three stylesheets' => [
                'sourceDocument' => FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                'cssHttpFixtures' => [
                    ResponseFactory::create('text/plain', 'body { color: #ff0000 }'),
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #ff0000 }'),
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #ff0000 }'),
                ],
                'expectedInvalidResponseContentTypeExceptions' => [
                    md5('http://example.com/one.css') => [
                        'url' => 'http://example.com/one.css',
                        'message' => 'Invalid content type "text/plain"',
                        'contentTypeString' => 'text/plain',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider prepareFromLinkedStylesheetsDataProvider
     *
     * @param string $sourceDocument
     * @param array $cssHttpFixtures
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     * @throws UnparseableContentTypeException
     */
    public function testReset(
        string $sourceDocument,
        array $cssHttpFixtures
    ) {
        $localProxyResource = $this->createLocalProxyResource($sourceDocument, $cssHttpFixtures);
        $localPaths = $localProxyResource->prepare();

        foreach ($localPaths as $localPath) {
            $this->assertFileExists($localPath);
        }

        $localProxyResource->reset();

        foreach ($localPaths as $localPath) {
            $this->assertFileNotExists($localPath);
        }
    }

    private function mergePrepareFromLinkedStylesheetsData(array $additionalTestData): array
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

    public function prepareFromLinkedStylesheetsDataProvider(): array
    {
        $internalServerErrorResponse = new Response(500);
        $curlUnableToResolveHostResponse = new ConnectException(
            'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
            new Request('GET', 'http://example.com/')
        );

        return [
            'single stylesheet' => [
                'sourceDocument' => FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
                'cssHttpFixtures' => [
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #ff0000 }'),
                ],
            ],
            'three stylesheets' => [
                'sourceDocument' => FixtureLoader::load('Html/minimal-html5-three-stylesheets.html'),
                'cssHttpFixtures' => [
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #ff0000 }'),
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #ff0000 }'),
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #ff0000 }'),
                ],
            ],
            'five stylesheets' => [
                'sourceDocument' => FixtureLoader::load('Html/minimal-html5-five-stylesheets.html'),
                'cssHttpFixtures' => [
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    ResponseFactory::createCssResponse('body { color: #ff0000 }'),
                    $curlUnableToResolveHostResponse,
                    $curlUnableToResolveHostResponse,
                    $curlUnableToResolveHostResponse,
                    ResponseFactory::createCssResponse('body { color: #00ff00 }'),
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #0000ff }'),
                ],
            ],
            'base element' => [
                'sourceDocument' => FixtureLoader::load('Html/base-element.html'),
                'cssHttpFixtures' => [
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #f0f0f0 }'),
                ],
            ],
        ];
    }

    private function createLocalProxyResource(
        string $sourceDocumentContent,
        array $cssHttpFixtures = []
    ): LocalProxyResource {
        $this->appendHttpFixtures(array_merge(
            [
                ResponseFactory::createHtmlResponse(),
                ResponseFactory::createHtmlResponse($sourceDocumentContent),
            ],
            $cssHttpFixtures
        ));

        $cssValidatorConfiguration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ]);
        $localProxyResource = new LocalProxyResource($cssValidatorConfiguration, $this->httpClient);

        return $localProxyResource;
    }

    /**
     * @param string $htmlDocument
     *
     * @return string[]
     */
    private function getCssLinkElementsFromHtmlDocument(string $htmlDocument): array
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

    private function getLocalPathFromFileUrlToValidate(string $urlToValidate): string
    {
        return str_replace('file:', '', $urlToValidate);
    }

    private function createLocalResourcePathPattern(string $fileType): string
    {
        return '/file:' . preg_quote(sys_get_temp_dir(), '/') . '\/[a-f0-9]{32}\.' . $fileType . '/';
    }
}
