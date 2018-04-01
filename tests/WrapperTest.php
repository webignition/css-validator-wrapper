<?php

namespace webignition\Tests\CssValidatorWrapper\Wrapper;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;
use QueryPath\Exception as QueryPathException;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\Tests\CssValidatorWrapper\AbstractBaseTest;
use webignition\Tests\CssValidatorWrapper\Factory\FixtureLoader;
use webignition\Tests\CssValidatorWrapper\Factory\ResponseFactory;

class WrapperTest extends AbstractBaseTest
{
    /**
     * @var Wrapper|MockInterface
     */
    private $wrapper;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->wrapper = new Wrapper();
        $this->wrapper->setHttpClient($this->httpClient);
    }

    /**
     * @dataProvider validateInvalidContentTypeOnRootWebResourceDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedExceptionType
     *
     * @throws QueryPathException
     * @throws InternetMediaTypeParseException
     * @throws InvalidValidatorOutputException
     */
    public function testValidateErrorOnRootWebResource($httpFixtures, $expectedExceptionType)
    {
        $this->appendHttpFixtures($httpFixtures);
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ]);

        $output = $this->wrapper->validate($configuration);

        $this->assertInstanceOf(CssValidatorOutput::class, $output);
        $this->assertTrue($output->hasException());

        $this->assertEquals($expectedExceptionType, $output->getException()->getType()->get());
    }

    /**
     * @return array
     */
    public function validateInvalidContentTypeOnRootWebResourceDataProvider()
    {
        $curl6ConnectException = new ConnectException(
            'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
            new Request('GET', 'http://example.com/')
        );

        $curl28ConnectException = new ConnectException(
            'cURL error 28: Operation timeout..',
            new Request('GET', 'http://example.com/')
        );

        return [
            'application/pdf' => [
                'httpFixtures' => [
                    ResponseFactory::create('application/pdf'),
                ],
                'expectedExceptionType' => 'invalid-content-type:application/pdf'
            ],
            'text/plain' => [
                'httpFixtures' => [
                    ResponseFactory::create('text/plain'),
                ],
                'expectedExceptionType' => 'invalid-content-type:text/plain'
            ],
            'http 410' => [
                'httpFixtures' => [
                    ResponseFactory::createHtmlResponse('', 410),
                    ResponseFactory::createHtmlResponse('', 410),
                ],
                'expectedExceptionType' => 'http410',
            ],
            'http 404' => [
                'httpFixtures' => [
                    ResponseFactory::createHtmlResponse('', 404),
                    ResponseFactory::createHtmlResponse('', 404),
                ],
                'expectedExceptionType' => 'http404',
            ],
            'http 500' => [
                'httpFixtures' => [
                    ResponseFactory::createHtmlResponse('', 500),
                    ResponseFactory::createHtmlResponse('', 500),
                ],
                'expectedExceptionType' => 'http500',
            ],
            'curl 6' => [
                'httpFixtures' => [
                    $curl6ConnectException,
                    $curl6ConnectException,
                ],
                'expectedExceptionType' => 'curl6',
            ],
            'curl 28' => [
                'httpFixtures' => [
                    $curl28ConnectException,
                    $curl28ConnectException,
                ],
                'expectedExceptionType' => 'curl28',
            ],
        ];
    }

    /**
     * @dataProvider validateErrorOnLinkedCssResourceDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedErrorMessage
     *
     * @throws InternetMediaTypeParseException
     * @throws QueryPathException
     * @throws InvalidValidatorOutputException
     */
    public function testValidateErrorOnLinkedCssResource($httpFixtures, $expectedErrorMessage)
    {
        $this->appendHttpFixtures($httpFixtures);

        $this->setCssValidatorRawOutput(
            $this->loadCssValidatorRawOutputFixture('no-messages')
        );

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ]);

        $output = $this->wrapper->validate($configuration);

        $this->assertInstanceOf(CssValidatorOutput::class, $output);

        $this->assertEquals(1, $output->getErrorCount());

        $errorsForLinkedStylesheet = $output->getErrorsByUrl('http://example.com/style.css');

        $this->assertCount(1, $errorsForLinkedStylesheet);
        $this->assertEquals($expectedErrorMessage, $errorsForLinkedStylesheet[0]->getMessage());
    }

    /**
     * @return array
     */
    public function validateErrorOnLinkedCssResourceDataProvider()
    {
        $minimalHtml5SingleStylesheetHttpFixture = ResponseFactory::createHtmlResponse(
            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
        );

        $curl6ConnectException = new ConnectException(
            'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
            new Request('GET', 'http://example.com/')
        );

        $curl28ConnectException = new ConnectException(
            'cURL error 28: Operation timed out.',
            new Request('GET', 'http://example.com/')
        );

        return [
            'invalid content type' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    ResponseFactory::create(
                        'text/plain'
                    ),
                ],
                'expectedErrorMessage' => 'invalid-content-type:text/plain',
            ],
            'http 404' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    new Response(404),
                    new Response(404),
                ],
                'expectedErrorMessage' => 'http-error:404',
            ],
            'http 500' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    new Response(500),
                    new Response(500),
                ],
                'expectedErrorMessage' => 'http-error:500',
            ],
            'curl 6' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $curl6ConnectException,
                    $curl6ConnectException,
                ],
                'expectedErrorMessage' => 'curl-error:6',
            ],
            'curl 28' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $curl28ConnectException,
                    $curl28ConnectException,
                ],
                'expectedErrorMessage' => 'curl-error:28',
            ],
        ];
    }

    /**
     * @dataProvider validateSuccessDataProvider
     *
     * @param array $httpFixtures
     * @param string $cssValidatorRawOutput
     * @param array $configurationValues
     * @param int $expectedWarningCount
     * @param int $expectedErrorCount
     * @param array $expectedErrorCountByUrl
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidValidatorOutputException
     * @throws QueryPathException
     */
    public function testValidateSuccess(
        $httpFixtures,
        $cssValidatorRawOutput,
        $configurationValues,
        $expectedWarningCount,
        $expectedErrorCount,
        $expectedErrorCountByUrl = []
    ) {
        $this->appendHttpFixtures($httpFixtures);
        $this->setCssValidatorRawOutput($cssValidatorRawOutput);

        $configuration = new Configuration(array_merge([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ], $configurationValues));

        $output = $this->wrapper->validate($configuration);

        $this->assertFalse($output->hasException());
        $this->assertEquals($expectedWarningCount, $output->getWarningCount());
        $this->assertEquals($expectedErrorCount, $output->getErrorCount());

        foreach ($expectedErrorCountByUrl as $url => $expectedErrorCountForUrl) {
            $this->assertCount($expectedErrorCountForUrl, $output->getErrorsByUrl($url));
        }
    }

    /**
     * @return array
     */
    public function validateSuccessDataProvider()
    {
        $minimalHtml5HttpFixture = ResponseFactory::createHtmlResponse(
            FixtureLoader::load('Html/minimal-html5.html')
        );

        $minimalHtml5SingleStylesheetHttpFixture = ResponseFactory::createHtmlResponse(
            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
        );

        $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture = ResponseFactory::createHtmlResponse(
            FixtureLoader::load('Html/minimal-html5-two-stylesheets-different-domains.html')
        );

        $minimalHtml5TThreeStylesheetsHttpFixture = ResponseFactory::createHtmlResponse(
            FixtureLoader::load('Html/minimal-html5-three-stylesheets.html')
        );

        $minimalHtml5MalformedSingleStylesheetHttpFixture = ResponseFactory::createHtmlResponse(
            FixtureLoader::load('Html/minimal-html5-malformed-single-stylesheet.html')
        );

        $genericCssHttpFixture = ResponseFactory::createCssResponse('foo');

        return [
            'ignore false image data url messages' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'incorrect-data-url-background-image-errors'
                ),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_IGNORE_FALSE_DATA_URL_MESSAGES => true,
                    ]),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore warnings' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('single-warning'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                    ]),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension issues:warn and ignore warnings' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('vendor-specific-at-rules'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                        OutputParserConfiguration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS => true,
                    ]),
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension warnings' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                    ]),
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_IGNORE,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension errors' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_IGNORE_VENDOR_EXTENSION_ISSUES => true,
                    ]),
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_IGNORE,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'domains to ignore: ignore none' => [
                'httpFixtures' => [
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
                'expectedErrorCountByUrl' => [
                    'http://one.example.com/style.css' => 1,
                    'http://two.example.com/style.css' => 2,
                ],
            ],
            'domains to ignore: ignore first of two' => [
                'httpFixtures' => [
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_REF_DOMAINS_TO_IGNORE => [
                            'one.example.com',
                        ],
                    ]),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 2,
                'expectedErrorCountByUrl' => [
                    'http://two.example.com/style.css' => 2,
                ],
            ],
            'domains to ignore: ignore second of two' => [
                'httpFixtures' => [
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_REF_DOMAINS_TO_IGNORE => [
                            'two.example.com',
                        ],
                    ]),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
                'expectedErrorCountByUrl' => [
                    'http://one.example.com/style.css' => 1,
                ],
            ],
            'domains to ignore: ignore both' => [
                'httpFixtures' => [
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_REF_DOMAINS_TO_IGNORE => [
                            'one.example.com',
                            'two.example.com',
                        ],
                    ]),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'encoded ampersands in css urls' => [
                'httpFixtures' => [
                    $minimalHtml5TThreeStylesheetsHttpFixture,
                    $minimalHtml5TThreeStylesheetsHttpFixture,
                    $minimalHtml5HttpFixture,
                    $minimalHtml5HttpFixture,
                    $minimalHtml5HttpFixture,
                    $minimalHtml5HttpFixture,
                    $minimalHtml5HttpFixture,
                    $minimalHtml5HttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 no css no linked resources' => [
                'httpFixtures' => [
                    $minimalHtml5HttpFixture,
                    $minimalHtml5HttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 content type with charset no css no linked resources' => [
                'httpFixtures' => [
                    ResponseFactory::create(
                        'text/html; charset=utf-8',
                        FixtureLoader::load('Html/minimal-html5.html')
                    ),
                    ResponseFactory::create(
                        'text/html; charset=utf-8',
                        FixtureLoader::load('Html/minimal-html5.html')
                    ),
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 malformed single linked resource' => [
                'httpFixtures' => [
                    $minimalHtml5MalformedSingleStylesheetHttpFixture,
                    $minimalHtml5MalformedSingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'configurationValues' => [],
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: warn' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                ],
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_ERROR,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
                'httpFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('vendor-specific-at-rules'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
                        OutputParserConfiguration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS => true,
                    ]),
                ],
                'expectedWarningCount' => 12,
                'expectedErrorCount' => 0,
            ],
        ];
    }

    /**
     * @param string $rawOutput
     */
    private function setCssValidatorRawOutput($rawOutput)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'shell_exec'
        )->andReturn(
            $rawOutput
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function loadCssValidatorRawOutputFixture($name)
    {
        return file_get_contents(__DIR__ . '/Fixtures/CssValidatorOutput/' . $name . '.txt');
    }
}
