<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use GuzzleHttp\Psr7\Uri;
use Mockery\MockInterface;
use webignition\CssValidatorOutput\Model\ErrorMessage;
use webignition\CssValidatorOutput\Model\MessageList;
use webignition\CssValidatorOutput\Model\ObservationResponse;
use webignition\CssValidatorOutput\Model\Options;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\CommandExecutor;
use webignition\CssValidatorWrapper\CommandFactory;
use webignition\CssValidatorWrapper\SourceType;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\CssValidatorWrapper\OutputMutator;
use webignition\CssValidatorWrapper\SourceHandler;
use webignition\CssValidatorWrapper\SourceStorage;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFactory;
use webignition\CssValidatorWrapper\Wrapper;
use webignition\UrlSourceMap\Source;
use webignition\UrlSourceMap\SourceMap;
use webignition\WebResource\WebPage\WebPage;

class WrapperTest extends \PHPUnit\Framework\TestCase
{
    const JAVA_EXECUTABLE_PATH = '/usr/bin/java';
    const CSS_VALIDATOR_JAR_PATH = 'css-validator.jar';
    const CSS_VALIDATOR_COMMAND = '/java -jar css-validator.jar -output ucn -vextwarning true "%s" 2>&1';

    public function testValidateUnknownSourceExceptionForWebPage()
    {
        $webPage = WebPageFactory::create('content', new Uri('http://example.com/'));
        $wrapper = $this->createWrapper(
            new SourceStorage(),
            new CommandFactory(self::JAVA_EXECUTABLE_PATH, self::CSS_VALIDATOR_JAR_PATH),
            new CommandExecutor(new OutputParser())
        );

        $sourceHandler = new SourceHandler($webPage, new SourceMap());

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage('Unknown source "http://example.com/"');

        $wrapper->validate($sourceHandler, VendorExtensionSeverityLevel::LEVEL_WARN);
    }

    public function testValidateUnknownSourceExceptionForLinkedCssResource()
    {
        $webPage = WebPageFactory::create(
            FixtureLoader::load('Html/minimal-html5-single-stylesheet.html'),
            new Uri('http://example.com/')
        );

        $wrapper = $this->createWrapper(
            new SourceStorage(),
            new CommandFactory(self::JAVA_EXECUTABLE_PATH, self::CSS_VALIDATOR_JAR_PATH),
            new CommandExecutor(new OutputParser())
        );


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
        CommandFactory $commandFactory,
        CommandExecutor $commandExecutor,
        SourceMap $sourceMap,
        string $sourceFixture,
        string $sourceUrl,
        string $vendorExtensionSeverityLevel,
        OutputParserConfiguration $outputParserConfiguration,
        array $expectedMessages,
        int $expectedWarningCount,
        int $expectedErrorCount,
        array $expectedErrorCountByUrl = []
    ) {
        $webPage = WebPageFactory::create($sourceFixture, new Uri($sourceUrl));
        $wrapper = $this->createWrapper($sourceStorage, $commandFactory, $commandExecutor);

        $sourceHandler = new SourceHandler($webPage, $sourceMap);

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
        $this->assertEquals($expectedMessages, array_values($messageList->getMessages()));

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
        $cssWithImportPath = FixtureLoader::getPath('Css/valid-with-import.css');

        $singleStylesheetHtmlRelBeforeHref = str_replace(
            '<link href="/style.css" rel="stylesheet">',
            '<link rel="stylesheet" href="/style.css">',
            $singleStylesheetHtml
        );

        $singleMbStylesheetHtml = str_replace(
            '"/style.css"',
            '"/搜.css"',
            $singleStylesheetHtml
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

        $singleStylesheetWithImportsSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/style.css', 'file:' . $cssWithImportPath),
            new Source(
                'http://example.com/one.css',
                'file:' . FixtureLoader::getPath('Css/one.css'),
                SourceType::TYPE_IMPORT
            ),
        ]);

        $singleStylesheetUnavailableSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/style.css'),
        ]);

        $singleMbStylesheetValidNoMessagesSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/%E6%90%9C.css', 'file:' . $cssNoMessagesPath),
        ]);

        $outputParserConfiguration = new OutputParserConfiguration();

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
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $noStylesheetsSourceMap,
                'sourceFixture' => $noStylesheetsHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
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
                            '<link rel="stylesheet" href="/style.css">',
                        ],
                        [
                            '<link rel="stylesheet" href="file:' . $cssNoMessagesPath . '">',
                        ],
                        $singleStylesheetHtmlRelBeforeHref
                    ),
                    $singleStylesheetValidNoMessagesSourceMap,
                    [
                        'http://example.com/style.css',
                    ]
                ),
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleStylesheetHtmlRelBeforeHref,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
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
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleEmptyHrefStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
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
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleStylesheetUnavailableSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
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
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList([
                                new ErrorMessage('title content', 3, '.bar', ''),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $noStylesheetsSourceMap,
                'sourceFixture' => $noStylesheetsHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
                'expectedMessages' => [
                    new ErrorMessage('title content', 3, '.bar', ''),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'html5 with single linked stylesheet, single error in linked stylesheet' => [
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
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList([
                                new ErrorMessage('title content', 2, '.foo', 'file:/tmp/style-hash.css'),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                ]),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
                'expectedMessages' => [
                    new ErrorMessage('title content', 2, '.foo', 'http://example.com/style.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'big5 document with no charset, charset supplied, single linked stylesheet, error stylesheet' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/%E6%90%9C.css', 'file:/tmp/style-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/搜.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:' . $cssNoMessagesPath . '" rel="stylesheet">',
                        ],
                        $singleMbStylesheetHtml
                    ),
                    $singleMbStylesheetValidNoMessagesSourceMap,
                    [
                        'http://example.com/%E6%90%9C.css',
                    ]
                ),
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList([
                                new ErrorMessage('title content', 2, '.foo', 'file:/tmp/style-hash.css'),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleMbStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleMbStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
                'expectedMessages' => [
                    new ErrorMessage('title content', 2, '.foo', 'http://example.com/%E6%90%9C.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'html5 with single linked CSS resource with import, no messages' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://example.com/one.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:' . $cssWithImportPath . '" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    $singleStylesheetWithImportsSourceMap,
                    [
                        'http://example.com/style.css',
                        'http://example.com/one.css',
                    ]
                ),
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                    [
                        'expectedUrl' => 'file:/tmp/valid-no-messages-hash.css',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/valid-no-messages-hash.css',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/valid-no-messages-hash.css'
                    ],
                ]),
                'sourceMap' => $singleStylesheetWithImportsSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'html5 with single linked CSS resource with import, error in import' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://example.com/one.css', 'file:/tmp/invalid-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:' . $cssWithImportPath . '" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    $singleStylesheetWithImportsSourceMap,
                    [
                        'http://example.com/style.css',
                        'http://example.com/one.css',
                    ]
                ),
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                    [
                        'expectedUrl' => 'file:/tmp/invalid-hash.css',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/invalid-hash.css',
                            new MessageList([
                                new ErrorMessage('title content', 2, '.foo', 'file:/tmp/invalid-hash.css'),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/invalid-hash.css'
                    ],
                ]),
                'sourceMap' => $singleStylesheetWithImportsSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
                'expectedMessages' => [
                    new ErrorMessage('title content', 2, '.foo', 'http://example.com/one.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'html5 with single linked CSS resource with import, error in linked stylesheet, error in import' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectations(
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/invalid-link-hash.css'),
                        new Source('http://example.com/one.css', 'file:/tmp/invalid-import-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:' . $cssWithImportPath . '" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    $singleStylesheetWithImportsSourceMap,
                    [
                        'http://example.com/style.css',
                        'http://example.com/one.css',
                    ]
                ),
                'commandFactory' => $this->createCommandFactory([
                    [
                        'expectedUrl' => 'file:/tmp/web-page-hash.html',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                    [
                        'expectedUrl' => 'file:/tmp/invalid-import-hash.css',
                        'expectedVendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                    ],
                ]),
                'commandExecutor' => $this->createCommandExecutor([
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/web-page-hash.html',
                            new MessageList([
                                new ErrorMessage('title content', 1, '.bar', 'file:/tmp/invalid-link-hash.css'),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/invalid-import-hash.css',
                            new MessageList([
                                new ErrorMessage('title content', 2, '.foo', 'file:/tmp/invalid-import-hash.css'),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => $outputParserConfiguration,
                        'expectedResourceUrl' => 'file:/tmp/invalid-import-hash.css'
                    ],
                ]),
                'sourceMap' => $singleStylesheetWithImportsSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => $outputParserConfiguration,
                'expectedMessages' => [
                    new ErrorMessage('title content', 1, '.bar', 'http://example.com/style.css'),
                    new ErrorMessage('title content', 2, '.foo', 'http://example.com/one.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 2,
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

    private function createWrapper(
        SourceStorage $sourceStorage,
        CommandFactory $commandFactory,
        CommandExecutor $commandExecutor
    ): Wrapper {
        return new Wrapper(
            $sourceStorage,
            new OutputMutator(),
            $commandFactory,
            $commandExecutor
        );
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

    /**
     * @return MockInterface|CommandExecutor
     */
    private function createCommandExecutor(array $calls): MockInterface
    {
        $commandExecutor = \Mockery::mock(CommandExecutor::class);

        foreach ($calls as $call) {
            $output = $call['output'];
            $expectedOutputParserConfiguration = $call['expectedOutputParserConfiguration'];
            $expectedResourceUrl = $call['expectedResourceUrl'];

            $expectedCommand = sprintf(
                self::CSS_VALIDATOR_COMMAND,
                $expectedResourceUrl
            );

            $commandExecutor
                ->shouldReceive('execute')
                ->with($expectedCommand, $expectedOutputParserConfiguration)
                ->andReturn($output);
        }

        return $commandExecutor;
    }

    /**
     * @return MockInterface|CommandFactory
     */
    private function createCommandFactory(array $calls): MockInterface
    {
        $commandFactory = \Mockery::mock(CommandFactory::class);

        foreach ($calls as $call) {
            $expectedUrl = $call['expectedUrl'];
            $expectedVendorExtensionSeverityLevel = $call['expectedVendorExtensionSeverityLevel'];

            $commandFactory
                ->shouldReceive('create')
                ->with($expectedUrl, $expectedVendorExtensionSeverityLevel)
                ->andReturn(sprintf(
                    self::CSS_VALIDATOR_COMMAND,
                    $expectedUrl
                ));

        }

        return $commandFactory;
    }

    private function createValidationOutput(string $observationResponseRef, MessageList $messageList): ValidationOutput
    {
        $options = new Options(false, 'ucn', 'en', 0, 'all', 'css3');
        $observationResponse = new ObservationResponse($observationResponseRef, new \DateTime(), $messageList);

        return new ValidationOutput($options, $observationResponse);
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
