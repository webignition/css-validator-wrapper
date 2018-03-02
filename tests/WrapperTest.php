<?php

namespace webignition\Tests\CssValidatorWrapper\Wrapper;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\Request;
use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper;
use webignition\Tests\CssValidatorWrapper\BaseTest;

class WrapperTest extends BaseTest
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
    }

    /**
     * @dataProvider hasConfigurationDataProvider
     *
     * @param $configuration
     * @param $expectedHasConfiguration
     */
    public function testHasConfiguration($configuration, $expectedHasConfiguration)
    {
        if (!is_null($configuration)) {
            $this->wrapper->setConfiguration($configuration);
        }

        $this->assertEquals($expectedHasConfiguration, $this->wrapper->hasConfiguration());
    }

    /**
     * @return array
     */
    public function hasConfigurationDataProvider()
    {
        return [
            'no configuration' => [
                'configuration' => null,
                'expectedHasConfiguration' => false,
            ],
            'has configuration' => [
                'configuration' => new Configuration([]),
                'expectedHasConfiguration' => true,
            ],
        ];
    }

    public function testValidateWithNoConfiguration()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to validate; configuration not set');
        $this->expectExceptionCode(Wrapper::INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET);

        $this->wrapper->validate();
    }

    /**
     * @dataProvider validateInvalidContentTypeOnRootWebResourceDataProvider
     *
     * @param array  $responseFixtures
     * @param string $expectedExceptionType
     */
    public function testValidateErrorOnRootWebResource($responseFixtures, $expectedExceptionType)
    {
        $httpClient = $this->createHttpClient($responseFixtures);
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_HTTP_CLIENT => $httpClient,
        ]);

        $this->wrapper->setConfiguration($configuration);
        $output = $this->wrapper->validate();

        $this->assertInstanceOf(CssValidatorOutput::class, $output);
        $this->assertTrue($output->hasException());

        $this->assertEquals($expectedExceptionType, $output->getException()->getType()->get());
    }

    /**
     * @return array
     */
    public function validateInvalidContentTypeOnRootWebResourceDataProvider()
    {
        return [
            'application/pdf' => [
                'responseFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:application/pdf",
                ],
                'expectedExceptionType' => 'invalid-content-type:application/pdf'
            ],
            'text/plain' => [
                'responseFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/plain",
                ],
                'expectedExceptionType' => 'invalid-content-type:text/plain'
            ],
            'http 410' => [
                'responseFixtures' => [
                    "HTTP/1.1 410 OK\nContent-type:text/html",
                    "HTTP/1.1 410 OK\nContent-type:text/html",
                ],
                'expectedExceptionType' => 'http410',
            ],
            'http 404' => [
                'responseFixtures' => [
                    "HTTP/1.1 404 OK\nContent-type:text/html",
                    "HTTP/1.1 404 OK\nContent-type:text/html",
                ],
                'expectedExceptionType' => 'http404',
            ],
            'http 500' => [
                'responseFixtures' => [
                    "HTTP/1.1 500 OK\nContent-type:text/html",
                    "HTTP/1.1 500 OK\nContent-type:text/html",
                ],
                'expectedExceptionType' => 'http500',
            ],
            'curl 6' => [
                'responseFixtures' => [
                    new ConnectException(
                        'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
                        new Request('GET', 'http://example.com/')
                    ),
                ],
                'expectedExceptionType' => 'curl6',
            ],
            'curl 28' => [
                'responseFixtures' => [
                    new ConnectException(
                        'cURL error 28: Operation timeout..',
                        new Request('GET', 'http://example.com/')
                    ),
                ],
                'expectedExceptionType' => 'curl28',
            ],
        ];
    }

    /**
     * @dataProvider validateErrorOnLinkedCssResourceDataProvider
     *
     * @param array $responseFixtures
     * @param string $expectedErrorMessage
     */
    public function testValidateErrorOnLinkedCssResource($responseFixtures, $expectedErrorMessage)
    {
        $this->setCssValidatorRawOutput(
            $this->loadCssValidatorRawOutputFixture('no-messages')
        );

        $httpClient = $this->createHttpClient($responseFixtures);
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_HTTP_CLIENT => $httpClient,
        ]);

        $this->wrapper->setConfiguration($configuration);
        $output = $this->wrapper->validate();

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
        $minimalHtml5SingleStylesheetHttpFixture = $this->createHttpFixture(
            'text/html',
            $this->loadHtmlDocumentFixture('minimal-html5-single-stylesheet')
        );

        return [
            'invalid content type' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $this->createHttpFixture(
                        'text/plain',
                        'foo'
                    ),
                ],
                'expectedErrorMessage' => 'invalid-content-type:text/plain',
            ],
            'http 404' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    "HTTP/1.1 404",
                    "HTTP/1.1 404",
                ],
                'expectedErrorMessage' => 'http-error:404',
            ],
            'http 500' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    "HTTP/1.1 500",
                    "HTTP/1.1 500",
                ],
                'expectedErrorMessage' => 'http-error:500',
            ],
            'curl 6' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    new ConnectException(
                        'cURL error 6: Couldn\'t resolve host. The given remote host was not resolved.',
                        new Request('GET', 'http://example.com/')
                    ),
                ],
                'expectedErrorMessage' => 'curl-error:6',
            ],
            'curl 28' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    new ConnectException(
                        'cURL error 28: Operation timed out.',
                        new Request('GET', 'http://example.com/')
                    ),
                ],
                'expectedErrorMessage' => 'curl-error:28',
            ],
        ];
    }

    /**
     * @dataProvider validateDataProvider
     *
     * @param array $responseFixtures
     * @param string $cssValidatorRawOutput
     * @param array $configurationValues
     * @param int $expectedWarningCount
     * @param int $expectedErrorCount
     * @param array $expectedErrorCountByUrl
     */
    public function testValidateFoo(
        $responseFixtures,
        $cssValidatorRawOutput,
        $configurationValues,
        $expectedWarningCount,
        $expectedErrorCount,
        $expectedErrorCountByUrl = []
    ) {
        $this->setCssValidatorRawOutput($cssValidatorRawOutput);

        $httpClient = $this->createHttpClient($responseFixtures);
        $configuration = new Configuration(array_merge([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_HTTP_CLIENT => $httpClient,
        ], $configurationValues));

        $this->wrapper->setConfiguration($configuration);
        $output = $this->wrapper->validate();

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
    public function validateDataProvider()
    {
        $minimalHtml5HttpFixture = $this->createHttpFixture(
            'text/html',
            $this->loadHtmlDocumentFixture('minimal-html5')
        );

        $minimalHtml5SingleStylesheetHttpFixture = $this->createHttpFixture(
            'text/html',
            $this->loadHtmlDocumentFixture('minimal-html5-single-stylesheet')
        );

        $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture = $this->createHttpFixture(
            'text/html',
            $this->loadHtmlDocumentFixture('minimal-html5-two-stylesheets-different-domains')
        );

        $genericCssHttpFixture = $this->createHttpFixture(
            'text/css',
            'foo'
        );

        return [
            'ignore false image data url messages' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'incorrect-data-url-background-image-errors'
                ),
                'configurationValues' => [
                    'flags' => [
                        Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES
                    ],
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore warnings' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('single-warning'),
                'configurationValues' => [
                    'flags' => [
                        Flags::FLAG_IGNORE_WARNINGS
                    ],
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension issues:warn and ignore warnings' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('vendor-specific-at-rules'),
                'configurationValues' => [
                    'flags' => [
                        Flags::FLAG_IGNORE_WARNINGS
                    ],
                    'vendor-extension-severity-level' => VendorExtensionSeverityLevel::LEVEL_WARN,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension warnings' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'configurationValues' => [
                    'flags' => [
                        Flags::FLAG_IGNORE_WARNINGS
                    ],
                    'vendor-extension-severity-level' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension errors' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'configurationValues' => [
                    'vendor-extension-severity-level' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'domains to ignore: ignore none' => [
                'responseFixtures' => [
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
                'responseFixtures' => [
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
                'configurationValues' => [
                    'domains-to-ignore' => [
                        'one.example.com',
                    ],
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 2,
                'expectedErrorCountByUrl' => [
                    'http://two.example.com/style.css' => 2,
                ],
            ],
            'domains to ignore: ignore second of two' => [
                'responseFixtures' => [
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
                'configurationValues' => [
                    'domains-to-ignore' => [
                        'two.example.com',
                    ],
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
                'expectedErrorCountByUrl' => [
                    'http://one.example.com/style.css' => 1,
                ],
            ],
            'domains to ignore: ignore both' => [
                'responseFixtures' => [
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
                'configurationValues' => [
                    'domains-to-ignore' => [
                        'one.example.com',
                        'two.example.com',
                    ],
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'encoded ampersands in css urls' => [
                'responseFixtures' => [
                    $this->createHttpFixture(
                        'text/html',
                        $this->loadHtmlDocumentFixture('minimal-html5-three-stylesheets')
                    ),
                    $this->createHttpFixture(
                        'text/html',
                        $this->loadHtmlDocumentFixture('minimal-html5-three-stylesheets')
                    ),
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
                'responseFixtures' => [
                    $minimalHtml5HttpFixture,
                    $minimalHtml5HttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 content type with charset no css no linked resources' => [
                'responseFixtures' => [
                    $this->createHttpFixture(
                        'text/html; charset=utf-8',
                        $this->loadHtmlDocumentFixture('minimal-html5')
                    ),
                    $this->createHttpFixture(
                        'text/html; charset=utf-8',
                        $this->loadHtmlDocumentFixture('minimal-html5')
                    ),
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 malformed single linked resource' => [
                'responseFixtures' => [
                    $this->createHttpFixture(
                        'text/html',
                        $this->loadHtmlDocumentFixture('minimal-html5-malformed-single-stylesheet')
                    ),
                    $this->createHttpFixture(
                        'text/html',
                        $this->loadHtmlDocumentFixture('minimal-html5-malformed-single-stylesheet')
                    ),
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'responseFixtures' => [
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
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'configurationValues' => [
                    'vendor-extension-severity-level' =>  VendorExtensionSeverityLevel::LEVEL_WARN,
                ],
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'configurationValues' => [
                    'vendor-extension-severity-level' =>  VendorExtensionSeverityLevel::LEVEL_ERROR,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
                'responseFixtures' => [
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $minimalHtml5SingleStylesheetHttpFixture,
                    $genericCssHttpFixture,
                    $genericCssHttpFixture,
                ],
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('vendor-specific-at-rules'),
                'configurationValues' => [
                    'vendor-extension-severity-level' =>  VendorExtensionSeverityLevel::LEVEL_WARN,
                ],
                'expectedWarningCount' => 12,
                'expectedErrorCount' => 0,
            ],
        ];
    }

    /**
     * @dataProvider createConfigurationDataProvider
     *
     * @param array $configurationValues
     * @param string $expectedContentToValidate
     * @param string[] $expectedDomainsToIgnore
     * @param string $expectedJavaExecutablePath
     * @param string $expectedUrlToValidate
     * @param string $expectedVendorExtensionSeverityLevel
     * @param string[] $expectedFlags
     */
    public function testCreateConfiguration(
        $configurationValues,
        $expectedContentToValidate,
        $expectedDomainsToIgnore,
        $expectedJavaExecutablePath,
        $expectedUrlToValidate,
        $expectedVendorExtensionSeverityLevel,
        $expectedFlags
    ) {
        $this->wrapper->createConfiguration($configurationValues);

        $configuration = $this->wrapper->getConfiguration();

        $this->assertEquals($expectedContentToValidate, $configuration->getContentToValidate());
        $this->assertEquals($expectedDomainsToIgnore, $configuration->getDomainsToIgnore());
        $this->assertEquals($expectedJavaExecutablePath, $configuration->getJavaExecutablePath());
        $this->assertEquals($expectedUrlToValidate, $configuration->getUrlToValidate());
        $this->assertEquals($expectedVendorExtensionSeverityLevel, $configuration->getVendorExtensionSeverityLevel());

        foreach ($expectedFlags as $expectedFlag) {
            $this->assertTrue($configuration->hasFlag($expectedFlag));
        }
    }

    /**
     * @return array
     */
    public function createConfigurationDataProvider()
    {
        return [
            'defaults' => [
                'configurationValues' => [],
                'expectedContentToValidate' => null,
                'expectedDomainsToIgnore' => [],
                'expectedJavaExecutablePath' => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                'expectedUrlToValidate' => '',
                'expectedVendorExtensionSeverityLevel' => Configuration::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL,
                'expectedFlags' => [],
            ],
            'set to non-defaults' => [
                'configurationValues' => [
                    Configuration::CONFIG_KEY_CONTENT_TO_VALIDATE => 'foo',
                    Configuration::CONFIG_KEY_DOMAINS_TO_IGNORE => [
                        'foo',
                        'bar',
                    ],
                    Configuration::CONFIG_KEY_JAVA_EXECUTABLE_PATH => '/bin/java',
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/foo',
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL
                        => VendorExtensionSeverityLevel::LEVEL_ERROR,
                    Configuration::CONFIG_KEY_FLAGS => [
                        Flags::FLAG_IGNORE_WARNINGS,
                        FLags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                    ],

                ],
                'expectedContentToValidate' => 'foo',
                'expectedDomainsToIgnore' => [
                    'foo',
                    'bar',
                ],
                'expectedJavaExecutablePath' => '/bin/java',
                'expectedUrlToValidate' => 'http://example.com/foo',
                'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'expectedFlags' => [
                    Flags::FLAG_IGNORE_WARNINGS,
                    FLags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                ],
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
        return file_get_contents(__DIR__ . '/fixtures/raw-css-validator-output/' . $name . '.txt');
    }
}
