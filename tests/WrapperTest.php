<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;
use Psr\Http\Message\UriInterface;
use webignition\CssValidatorOutput\Model\ErrorMessage;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\CommandFactory;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\CssValidatorWrapper\OutputMutator;
use webignition\CssValidatorWrapper\Source;
use webignition\CssValidatorWrapper\SourceHandler;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\CssValidatorWrapper\SourceStorage;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureModifier;
use webignition\CssValidatorWrapper\Wrapper;
use webignition\WebResource\WebPage\WebPage;

class WrapperTest extends \PHPUnit\Framework\TestCase
{
    const JAVA_EXECUTABLE_PATH = '/usr/bin/java';
    const CSS_VALIDATOR_JAR_PATH = 'css-validator.jar';

    public function testValidateUnknownSourceExceptionForWebPage()
    {
        $webPage = $this->createWebPage('http://example.com/', 'content');
        $wrapper = $this->createWrapper(new SourceStorage());

        $sourceHandler = new SourceHandler($webPage, new SourceMap());

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage('Unknown source "http://example.com/"');

        $wrapper->validate($sourceHandler, VendorExtensionSeverityLevel::LEVEL_WARN);
    }

    public function testValidateUnknownSourceExceptionForLinkedCssResource()
    {
        $webPage = $this->createWebPage(
            'http://example.com/',
            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html')
        );
        $wrapper = $this->createWrapper(new SourceStorage());

        $sourceHandler = new SourceHandler($webPage, new SourceMap([
            new Source('http://example.com/', 'non-empty string'),
        ]));

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage('Unknown source "http://example.com/style.css"');

        $wrapper->validate($sourceHandler, VendorExtensionSeverityLevel::LEVEL_WARN);
    }

    /**
     * @dataProvider validateSuccessDataProvider
     */
    public function testValidateSuccess(
        SourceStorage $sourceStorage,
        SourceMap $sourceMap,
        string $sourceFixture,
        string $sourceUrl,
        string $cssValidatorRawOutput,
        string $vendorExtensionSeverityLevel,
        OutputParserConfiguration $outputParserConfiguration,
        array $expectedMessages,
        int $expectedWarningCount,
        int $expectedErrorCount,
        array $expectedErrorCountByUrl = []
    ) {
        $webPage = $this->createWebPage($sourceUrl, $sourceFixture);
        $wrapper = $this->createWrapper($sourceStorage);

        $sourceHandler = new SourceHandler($webPage, $sourceMap);

        $this->setCssValidatorRawOutput($cssValidatorRawOutput);

        /* @var ValidationOutput $output */
        $output = $wrapper->validate(
            $sourceHandler,
            $vendorExtensionSeverityLevel,
            $outputParserConfiguration
        );

        $this->assertInstanceOf(ValidationOutput::class, $output);

        $observationResponse = $output->getObservationResponse();
        $this->assertEquals($sourceUrl, $observationResponse->getRef());

        $messageList = $output->getMessages();
        $this->assertEquals($expectedWarningCount, $messageList->getWarningCount());
        $this->assertEquals($expectedErrorCount, $messageList->getErrorCount());
        $this->assertEquals($expectedMessages, $messageList->getMessages());

        foreach ($expectedErrorCountByUrl as $url => $expectedErrorCountForUrl) {
            /* @var array $errorsByUrl */
            $errorsByUrl = $messageList->getErrorsByRef($url);

            $this->assertCount($expectedErrorCountForUrl, $errorsByUrl);
        }
    }

    public function validateSuccessDataProvider(): array
    {
        $noStylesheetsHtml = FixtureLoader::load('Html/minimal-html5.html');
        $singleStylesheetHtml = FixtureLoader::load('Html/minimal-html5-single-stylesheet.html');
        $singleEmptyHrefStylesheetHtml = FixtureLoader::load('Html/minimal-html5-unavailable-stylesheet.html');
        $cssNoMessagesPath = FixtureLoader::getPath('Css/valid-no-messages.css');
        $singleStylesheetHtmlWithNewLinesInLinkElement = WebPageFixtureModifier::addLineReturnsToLinkElements(
            $singleStylesheetHtml,
            [
                '<link href="/style.css" rel="stylesheet">',
            ]
        );

        $noStylesheetsSourceMap = new SourceMap([
            new Source('http://example.com/', 'file:' . FixtureLoader::getPath('Html/minimal-html5.html')),
        ]);

        $singleStylesheetValidNoMessagesSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/style.css', 'file:' . $cssNoMessagesPath),
        ]);

        $singleStylesheetUnavailableSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/style.css'),
        ]);

        return [
            'html5 no css no linked resources' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ]),
                    $noStylesheetsHtml,
                    $noStylesheetsSourceMap,
                    []
                ),
                'sourceMap' => $noStylesheetsSourceMap,
                'sourceFixture' => $noStylesheetsHtml,
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'no-messages',
                    [
                        '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                    ]
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 with single linked CSS resource, no messages' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:' . $cssNoMessagesPath . '" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    $singleStylesheetValidNoMessagesSourceMap,
                    [
                        'http://example.com/style.css',
                    ]
                ),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'no-messages',
                    [
                        '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                    ]
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 with single linked CSS resource, new lines in link element, no messages' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link' . "\n            " . 'href="/style.css"' . "\n            " . 'rel="stylesheet">',
                        ],
                        [
                            '<link' . "\n            " .
                            'href="file:' . $cssNoMessagesPath . '"' . "\n            " .
                            'rel="stylesheet">',
                        ],
                        $singleStylesheetHtmlWithNewLinesInLinkElement
                    ),
                    $singleStylesheetValidNoMessagesSourceMap,
                    [
                        'http://example.com/style.css',
                    ]
                ),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleStylesheetHtmlWithNewLinesInLinkElement,
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'no-messages',
                    [
                        '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                    ]
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 with single empty linked CSS resource, no CSS to validate' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ]),
                    str_replace(
                        [
                            '<link href="" rel="stylesheet">',
                        ],
                        [
                            '<link href="">',
                        ],
                        $singleEmptyHrefStylesheetHtml
                    ),
                    $singleStylesheetValidNoMessagesSourceMap,
                    []
                ),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleEmptyHrefStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'no-messages',
                    [
                        '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                    ]
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 with unavailable CSS resource, file not found error is removed' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="/style.css">',
                        ],
                        $singleStylesheetHtml
                    ),
                    $singleStylesheetUnavailableSourceMap,
                    [
                        'http://example.com/style.css',
                    ]
                ),
                'sourceMap' => $singleStylesheetUnavailableSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'no-messages',
                    [
                        '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                    ]
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 with inline style, single error' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ]),
                    $noStylesheetsHtml,
                    $noStylesheetsSourceMap,
                    []
                ),
                'sourceMap' => $noStylesheetsSourceMap,
                'sourceFixture' => $noStylesheetsHtml,
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'single-error-within-markup',
                    [
                        '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                    ]
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedMessages' => [
                    new ErrorMessage('title content', 3, '.bar', ''),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'html5 with inline style, single error in linked stylesheet' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/style-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:' . $cssNoMessagesPath . '" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    $singleStylesheetValidNoMessagesSourceMap,
                    [
                        'http://example.com/style.css',
                    ]
                ),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'single-error-within-linked-stylesheet',
                    [
                        '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                        '{{ cssSourceUri }}' => 'file:/tmp/style-hash.css',
                    ]
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedMessages' => [
                    new ErrorMessage('title content', 2, '.foo', 'http://example.com/style.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
//            'domains to ignore: ignore none' => [
//                'httpFixtures' => [
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                ],
//                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
//                'configurationValues' => [],
//                'expectedWarningCount' => 0,
//                'expectedErrorCount' => 3,
//                'expectedErrorCountByUrl' => [
//                    'http://one.example.com/style.css' => 1,
//                    'http://two.example.com/style.css' => 2,
//                ],
//            ],
//            'domains to ignore: ignore first of two' => [
//                'httpFixtures' => [
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                ],
//                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
//                'configurationValues' => [
//                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
//                        OutputParserConfiguration::KEY_REF_DOMAINS_TO_IGNORE => [
//                            'one.example.com',
//                        ],
//                    ]),
//                ],
//                'expectedWarningCount' => 0,
//                'expectedErrorCount' => 2,
//                'expectedErrorCountByUrl' => [
//                    'http://two.example.com/style.css' => 2,
//                ],
//            ],
//            'domains to ignore: ignore second of two' => [
//                'httpFixtures' => [
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                ],
//                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
//                'configurationValues' => [
//                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
//                        OutputParserConfiguration::KEY_REF_DOMAINS_TO_IGNORE => [
//                            'two.example.com',
//                        ],
//                    ]),
//                ],
//                'expectedWarningCount' => 0,
//                'expectedErrorCount' => 1,
//                'expectedErrorCountByUrl' => [
//                    'http://one.example.com/style.css' => 1,
//                ],
//            ],
//            'domains to ignore: ignore both' => [
//                'httpFixtures' => [
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $minimalHtml5TwoStylesheetsDifferentDomainsHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                    $genericCssHttpFixture,
//                ],
//                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('domains-to-ignore'),
//                'configurationValues' => [
//                    Configuration::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION => new OutputParserConfiguration([
//                        OutputParserConfiguration::KEY_REF_DOMAINS_TO_IGNORE => [
//                            'one.example.com',
//                            'two.example.com',
//                        ],
//                    ]),
//                ],
//                'expectedWarningCount' => 0,
//                'expectedErrorCount' => 0,
//            ],
//            'encoded ampersands in css urls' => [
//                'httpFixtures' => [
//                    $minimalHtml5TThreeStylesheetsHttpFixture,
//                    $minimalHtml5TThreeStylesheetsHttpFixture,
//                    $minimalHtml5HttpFixture,
//                    $minimalHtml5HttpFixture,
//                    $minimalHtml5HttpFixture,
//                    $minimalHtml5HttpFixture,
//                    $minimalHtml5HttpFixture,
//                    $minimalHtml5HttpFixture,
//                ],
//                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
//                'configurationValues' => [],
//                'expectedWarningCount' => 0,
//                'expectedErrorCount' => 0,
//            ],
        ];
    }

    /**
     * @dataProvider validateSuccessOutputParserConfigurationDataProvider
     */
    public function testValidateSuccessOutputParserConfiguration(
        string $cssValidatorFixture,
        string $vendorExtensionSeverityLevel,
        OutputParserConfiguration $outputParserConfiguration,
        int $expectedWarningCount,
        int $expectedErrorCount,
        array $expectedErrorCountByUrl = []
    ) {
        $sourceUrl = 'http://example.com/';

        $htmlFixtureName = 'Html/minimal-html5.html';
        $noStylesheetsHtml = FixtureLoader::load($htmlFixtureName);

        $noStylesheetsSourceMap = new SourceMap([
            new Source('http://example.com/', 'file:' . FixtureLoader::getPath($htmlFixtureName)),
        ]);

        $sourceStorage = $this->createSourceStorageWithValidateExpectations(
            new SourceMap([
                new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
            ]),
            $noStylesheetsHtml,
            $noStylesheetsSourceMap,
            []
        );

        $webPage = $this->createWebPage($sourceUrl, $noStylesheetsHtml);
        $wrapper = $this->createWrapper($sourceStorage);

        $sourceHandler = new SourceHandler($webPage, $noStylesheetsSourceMap);

        $this->setCssValidatorRawOutput($this->loadCssValidatorRawOutputFixture(
            $cssValidatorFixture,
            [
                '{{ webPageUri }}' => 'file:/tmp/web-page-hash.html',
                '{{ cssSourceUri }}' => 'file:/tmp/css-hash.css',
            ]
        ));

        /* @var ValidationOutput $output */
        $output = $wrapper->validate(
            $sourceHandler,
            $vendorExtensionSeverityLevel,
            $outputParserConfiguration
        );

        $this->assertInstanceOf(ValidationOutput::class, $output);

        $messageList = $output->getMessages();
        $this->assertEquals($expectedWarningCount, $messageList->getWarningCount());
        $this->assertEquals($expectedErrorCount, $messageList->getErrorCount());

        foreach ($expectedErrorCountByUrl as $url => $expectedErrorCountForUrl) {
            /* @var array $errorsByUrl */
            $errorsByUrl = $messageList->getErrorsByRef($url);

            $this->assertCount($expectedErrorCountForUrl, $errorsByUrl);
        }
    }

    public function validateSuccessOutputParserConfigurationDataProvider(): array
    {
        return [
            'ignore false image data url messages' => [
                'cssValidatorFixture' => 'incorrect-data-url-background-image-errors',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_FALSE_DATA_URL_MESSAGES => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore warnings' => [
                'cssValidatorFixture' => 'single-warning',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension issues:warn and ignore warnings' => [
                'cssValidatorFixture' => 'vendor-specific-at-rules',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                    OutputParserConfiguration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension warnings' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension errors' => [
                'cssValidatorFixture' => 'three-vendor-extension-errors',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_VENDOR_EXTENSION_ISSUES => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: warn' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'cssValidatorFixture' => 'three-vendor-extension-errors',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'outputParserConfiguration' => new OutputParserConfiguration([]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
                'cssValidatorFixture' => 'vendor-specific-at-rules',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS => true,
                ]),
                'expectedWarningCount' => 12,
                'expectedErrorCount' => 0,
            ],
        ];
    }

    private function setCssValidatorRawOutput(string $rawOutput)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'shell_exec'
        )->andReturn(
            $rawOutput
        );
    }

    private function loadCssValidatorRawOutputFixture(string $name, array $replacements = []): string
    {
        $fixtureContent = file_get_contents(__DIR__ . '/Fixtures/CssValidatorOutput/' . $name . '.txt');

        foreach ($replacements as $search => $replace) {
            $fixtureContent = str_replace($search, $replace, $fixtureContent);
        }

        return $fixtureContent;
    }

    private function createWrapper(SourceStorage $sourceStorage): Wrapper
    {
        return new Wrapper(
            $sourceStorage,
            new OutputMutator(),
            new CommandFactory(),
            new OutputParser(),
            self::JAVA_EXECUTABLE_PATH,
            self::CSS_VALIDATOR_JAR_PATH
        );
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

    /**
     * @return MockInterface|SourceStorage
     */
    private function createSourceStorage()
    {
        $sourceStorage = \Mockery::mock(SourceStorage::class);

        return $sourceStorage;
    }

    private function createSourceStorageWithValidateExpectations(
        SourceMap $getPathsSourceMap,
        string $expectedStoreWebPageContent,
        SourceMap $expectedStoreSourceMap,
        array $expectedStoreStylesheetUrls
    ) {
        $sourceStorage = $this->createSourceStorage();

        $this->createSourceStorageStoreExpectation(
            $sourceStorage,
            $expectedStoreWebPageContent,
            $expectedStoreSourceMap,
            $expectedStoreStylesheetUrls
        );

        $this->createSourceStorageGetSourcesExpectation($sourceStorage, $getPathsSourceMap);
        $this->createSourceStorageDeleteAllExpectation($sourceStorage);

        return $sourceStorage;
    }

    private function createSourceStorageStoreExpectation(
        MockInterface $sourceStorageMock,
        string $expectedWebPageContent,
        SourceMap $expectedSourceMap,
        array $expectedStylesheetUrls
    ) {
        $sourceStorageMock
            ->shouldReceive('store')
            ->withArgs(function (
                WebPage $webPage,
                SourceMap $sourceMap,
                array $stylesheetUrls
            ) use (
                $expectedWebPageContent,
                $expectedSourceMap,
                $expectedStylesheetUrls
            ) {
                $this->assertEquals($expectedWebPageContent, $webPage->getContent());
                $this->assertEquals($expectedSourceMap, $sourceMap);
                $this->assertEquals($expectedStylesheetUrls, $stylesheetUrls);

                return true;
            });

        return $sourceStorageMock;
    }

    private function createSourceStorageGetSourcesExpectation(MockInterface $sourceStorageMock, SourceMap $paths)
    {
        $sourceStorageMock
            ->shouldReceive('getSources')
            ->andReturn($paths);

        return $sourceStorageMock;
    }

    private function createSourceStorageDeleteAllExpectation(MockInterface $sourceStorageMock)
    {
        $sourceStorageMock
            ->shouldReceive('deleteAll');

        return $sourceStorageMock;
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
