<?php

namespace webignition\Tests\CssValidatorWrapper\Wrapper;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use QueryPath\Exception as QueryPathException;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\LocalProxyResource;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\Tests\CssValidatorWrapper\AbstractBaseTest;
use webignition\Tests\CssValidatorWrapper\Factory\FixtureLoader;
use webignition\Tests\CssValidatorWrapper\Factory\ResponseFactory;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResourceInterfaces\InvalidContentTypeExceptionInterface;

class LocalProxyResourceTest extends AbstractBaseTest
{
    const REWRITTEN_STYLESHEET_LINK_PATTERN = '<link href="file:\/tmp\/[a-f0-9]{32}\.css" rel="stylesheet">';

    /**
     * @dataProvider prepareFromContentToValidateDataProvider
     *
     * @param string $content
     * @param string $expectedLocalResourcePathExtension
     *
     * @throws QueryPathException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws HttpException
     * @throws TransportException
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

    /**
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws QueryPathException
     * @throws TransportException
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
     * @throws InvalidContentTypeExceptionInterface
     * @throws QueryPathException
     * @throws TransportException
     */
    public function testPrepareWithLinkedStylesheetsReplacesCssLinks(
        $sourceDocument,
        $cssHttpFixtures,
        $expectedSourceCssElements,
        $expectedPreparedCssElementPatterns
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
     * @throws InvalidContentTypeExceptionInterface
     * @throws QueryPathException
     * @throws TransportException
     */
    public function testGetHttpExceptions(
        $sourceDocument,
        $cssHttpFixtures,
        $expectedHttpExceptions
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

    /**
     * @return array
     */
    public function getHttpExceptionsDataProvider()
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
     * @throws InvalidContentTypeExceptionInterface
     * @throws QueryPathException
     * @throws TransportException
     */
    public function testGetTransportExceptions(
        $sourceDocument,
        $cssHttpFixtures,
        $expectedTransportExceptions
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

    /**
     * @return array
     */
    public function getTransportExceptionsDataProvider()
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
     * @dataProvider prepareFromLinkedStylesheetsDataProvider
     *
     * @param string $sourceDocument
     * @param array $cssHttpFixtures
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws QueryPathException
     * @throws TransportException
     */
    public function testReset(
        $sourceDocument,
        $cssHttpFixtures
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
                'sourceDocument' => $this->loadHtmlDocumentFixture('minimal-html5-five-stylesheets'),
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
                'sourceDocument' => $this->loadHtmlDocumentFixture('base-element'),
                'cssHttpFixtures' => [
                    ResponseFactory::createCssResponse(),
                    ResponseFactory::createCssResponse('body { color: #f0f0f0 }'),
                ],
            ],
        ];
    }

    /**
     * @param string $sourceDocumentContent
     * @param array $cssHttpFixtures
     *
     * @return LocalProxyResource
     */
    private function createLocalProxyResource($sourceDocumentContent, array $cssHttpFixtures = [])
    {
        $mockHandler = new MockHandler(array_merge(
            [
                ResponseFactory::createHtmlResponse(),
                ResponseFactory::createHtmlResponse($sourceDocumentContent),
            ],
            $cssHttpFixtures
        ));

        $httpClient = new HttpClient(['handler' => HandlerStack::create($mockHandler)]);

        $cssValidatorConfiguration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ]);
        $localProxyResource = new LocalProxyResource($cssValidatorConfiguration);
        $localProxyResource->getWebResourceRetriever()->setHttpClient($httpClient);

        return $localProxyResource;
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
}
