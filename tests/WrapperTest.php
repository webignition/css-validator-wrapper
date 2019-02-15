<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests;

use GuzzleHttp\Psr7\Uri;
use Mockery\MockInterface;
use webignition\CssValidatorOutput\Model\ErrorMessage;
use webignition\CssValidatorOutput\Model\MessageList;
use webignition\CssValidatorOutput\Model\ObservationResponse;
use webignition\CssValidatorOutput\Model\Options;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Flags;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\CommandExecutor;
use webignition\CssValidatorWrapper\CommandFactory;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMutator;
use webignition\CssValidatorWrapper\SourceType;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\CssValidatorWrapper\OutputMutator;
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

        $remoteSources = new SourceMap([
            new Source('http://example.com/', 'non-empty string'),
        ]);

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage('Unknown source "http://example.com/style.css"');

        $wrapper->validate($webPage, $remoteSources, VendorExtensionSeverityLevel::LEVEL_WARN);
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
        array $domainsToIgnore,
        int $outputParserFlags,
        array $expectedMessages,
        int $expectedWarningCount,
        int $expectedErrorCount,
        array $expectedErrorCountByUrl = []
    ) {
        $webPage = WebPageFactory::create($sourceFixture, new Uri($sourceUrl));
        $wrapper = $this->createWrapper($sourceStorage, $commandFactory, $commandExecutor);

        /* @var ValidationOutput $output */
        $output = $wrapper->validate(
            $webPage,
            $sourceMap,
            $vendorExtensionSeverityLevel,
            $domainsToIgnore,
            $outputParserFlags
        );

        $this->assertInstanceOf(ValidationOutput::class, $output);

        if ($output instanceof ValidationOutput) {
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

        $singleStylesheetHtmlIgnoredDomain = str_replace(
            '<link href="/style.css" rel="stylesheet">',
            '<link href="http://foo.example.com/style.css" rel="stylesheet">',
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

        $singleStylesheetLackingStylesheetSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
        ]);

        $singleStylesheetWithImportsSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/style.css', 'file:' . $cssWithImportPath),
            new Source(
                'http://foo.example.com/import.css',
                'file:' . FixtureLoader::getPath('Css/one.css'),
                SourceType::TYPE_IMPORT
            ),
        ]);

        $singleStylesheetWithImportsImportDomainIgnoredSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/style.css', 'file:' . $cssWithImportPath),
        ]);

        $singleStylesheetUnavailableSourceMap = new SourceMap([
            new Source(
                'http://example.com/',
                'file:' . FixtureLoader::getPath('Html/minimal-html5-single-stylesheet.html')
            ),
            new Source('http://example.com/style.css'),
        ]);

        return [
            'no CSS' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $noStylesheetsSourceMap,
                    [],
                    new SourceMap(),
                    $noStylesheetsHtml,
                    new SourceMap(),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $noStylesheetsSourceMap,
                'sourceFixture' => $noStylesheetsHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'linked stylesheet, no messages' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetValidNoMessagesSourceMap,
                    [
                        'http://example.com/style.css',
                    ],
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link rel="stylesheet" href="/style.css">',
                        ],
                        [
                            '<link rel="stylesheet" href="file:/tmp/valid-no-messages-hash.css">',
                        ],
                        $singleStylesheetHtmlRelBeforeHref
                    ),
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleStylesheetHtmlRelBeforeHref,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'linked stylesheet, not in source map, url domain is ignored' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetLackingStylesheetSourceMap,
                    [
                        'http://foo.example.com/style.css',
                    ],
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="http://foo.example.com/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="http://foo.example.com/style.css">',
                        ],
                        $singleStylesheetHtmlIgnoredDomain
                    ),
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleStylesheetLackingStylesheetSourceMap,
                'sourceFixture' => $singleStylesheetHtmlIgnoredDomain,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [
                    'foo.example.com',
                ],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'empty-href linked stylesheet, no CSS to validate' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetValidNoMessagesSourceMap,
                    [],
                    new SourceMap(),
                    str_replace(
                        [
                            '<link href="" rel="stylesheet">',
                        ],
                        [
                            '<link href="">',
                        ],
                        $singleEmptyHrefStylesheetHtml
                    ),
                    new SourceMap(),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleEmptyHrefStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'unavailable linked stylesheet, stylesheet is ignored' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetUnavailableSourceMap,
                    [
                        'http://example.com/style.css',
                    ],
                    new SourceMap(),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="/style.css">',
                        ],
                        $singleStylesheetHtml
                    ),
                    new SourceMap(),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $singleStylesheetUnavailableSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'in-document CSS, single error' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $noStylesheetsSourceMap,
                    [],
                    new SourceMap(),
                    $noStylesheetsHtml,
                    new SourceMap(),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html',
                    ],
                ]),
                'sourceMap' => $noStylesheetsSourceMap,
                'sourceFixture' => $noStylesheetsHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [
                    new ErrorMessage('title content', 3, '.bar', ''),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'linked stylesheet, single error' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetValidNoMessagesSourceMap,
                    [
                        'http://example.com/style.css',
                    ],
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/style-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:/tmp/style-hash.css" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/style-hash.css'),
                    ]),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/style-hash.css'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                ]),
                'sourceMap' => $singleStylesheetValidNoMessagesSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [
                    new ErrorMessage('title content', 2, '.foo', 'http://example.com/style.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'linked stylesheet with import, no messages' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetWithImportsSourceMap,
                    [
                        'http://example.com/style.css',
                        'http://foo.example.com/import.css',
                    ],
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:/tmp/valid-no-messages-hash.css" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/valid-no-messages-hash.css',
                            new MessageList()
                        ),
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/valid-no-messages-hash.css'
                    ],
                ]),
                'sourceMap' => $singleStylesheetWithImportsSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'linked stylesheet with import, import domain ignored, no messages' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetWithImportsImportDomainIgnoredSourceMap,
                    [
                        'http://example.com/style.css',
                    ],
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:/tmp/valid-no-messages-hash.css" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ]),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                ]),
                'sourceMap' => $singleStylesheetWithImportsImportDomainIgnoredSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [
                    'foo.example.com',
                ],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'linked stylesheet with import, error in import' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetWithImportsSourceMap,
                    [
                        'http://example.com/style.css',
                        'http://foo.example.com/import.css',
                    ],
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/invalid-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:/tmp/valid-no-messages-hash.css" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/invalid-hash.css'),
                    ]),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/valid-no-messages-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/invalid-hash.css'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/invalid-hash.css',
                            new MessageList([
                                new ErrorMessage('title content', 2, '.foo', ''),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/invalid-hash.css'
                    ],
                ]),
                'sourceMap' => $singleStylesheetWithImportsSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [
                    new ErrorMessage('title content', 2, '.foo', 'http://foo.example.com/import.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 1,
            ],
            'linked stylesheet with import, error in linked stylesheet, error in import' => [
                'sourceStorage' => $this->createSourceStorageWithValidateExpectationsFoo(
                    new SourceMap(),
                    $singleStylesheetWithImportsSourceMap,
                    [
                        'http://example.com/style.css',
                        'http://foo.example.com/import.css',
                    ],
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/invalid-link-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/invalid-import-hash.css'),
                    ]),
                    str_replace(
                        [
                            '<link href="/style.css" rel="stylesheet">',
                        ],
                        [
                            '<link href="file:/tmp/invalid-link-hash.css" rel="stylesheet">',
                        ],
                        $singleStylesheetHtml
                    ),
                    new SourceMap([
                        new Source('http://example.com/style.css', 'file:/tmp/invalid-link-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/invalid-import-hash.css'),
                    ]),
                    new SourceMap([
                        new Source('http://example.com/', 'file:/tmp/web-page-hash.html'),
                        new Source('http://example.com/style.css', 'file:/tmp/invalid-link-hash.css'),
                        new Source('http://foo.example.com/import.css', 'file:/tmp/invalid-import-hash.css'),
                    ])
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
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/web-page-hash.html'
                    ],
                    [
                        'output' => $this->createValidationOutput(
                            'file:/tmp/invalid-import-hash.css',
                            new MessageList([
                                new ErrorMessage('title content', 2, '.foo', ''),
                            ])
                        ),
                        'expectedOutputParserConfiguration' => Flags::NONE,
                        'expectedResourceUrl' => 'file:/tmp/invalid-import-hash.css'
                    ],
                ]),
                'sourceMap' => $singleStylesheetWithImportsSourceMap,
                'sourceFixture' => $singleStylesheetHtml,
                'sourceUrl' => 'http://example.com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'domainsToIgnore' => [],
                'outputParserConfiguration' => Flags::NONE,
                'expectedMessages' => [
                    new ErrorMessage('title content', 1, '.bar', 'http://example.com/style.css'),
                    new ErrorMessage('title content', 2, '.foo', 'http://foo.example.com/import.css'),
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 2,
            ],
        ];
    }

    private function createWrapper(
        SourceStorage $sourceStorage,
        CommandFactory $commandFactory,
        CommandExecutor $commandExecutor
    ): Wrapper {
        return new Wrapper(
            new SourceInspector(),
            new SourceMutator(),
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

    private function createSourceStorageWithValidateExpectationsFoo(
        SourceMap $expectedStoreCssResourcesLocalSources,
        SourceMap $expectedStoreCssResourcesRemoteSources,
        array $expectedStoreCssResourcesStylesheetUrls,
        SourceMap $expectedStoreCssResourcesReturnSourceMap,
        $expectedStoreWebPageWebPageContent,
        SourceMap $expectedStoreWebPageLocalSourceMap,
        SourceMap $expectedStoreWebPageReturnSourceMap
    ) {
        $sourceStorage = $this->createSourceStorage();

        $this->createSourceStorageStoreCssResourcesExpectation(
            $sourceStorage,
            $expectedStoreCssResourcesRemoteSources,
            $expectedStoreCssResourcesLocalSources,
            $expectedStoreCssResourcesStylesheetUrls,
            $expectedStoreCssResourcesReturnSourceMap
        );

        $this->createSourceStorageStoreWebPageExpectation(
            $sourceStorage,
            $expectedStoreWebPageWebPageContent,
            $expectedStoreWebPageLocalSourceMap,
            $expectedStoreWebPageReturnSourceMap
        );

        return $sourceStorage;
    }

    private function createSourceStorageStoreCssResourcesExpectation(
        MockInterface $sourceStorage,
        SourceMap $expectedRemoteSources,
        SourceMap $expectedLocalSources,
        array $expectedStylesheetUrls,
        SourceMap $expectedReturnedSourceMap
    ) {
        $sourceStorage
            ->shouldReceive('storeCssResources')
            ->withArgs(function (
                SourceMap $remoteSources,
                SourceMap $localSources,
                array $stylesheetUrls
            ) use (
                $expectedRemoteSources,
                $expectedLocalSources,
                $expectedStylesheetUrls
            ) {
                $this->assertSame($expectedRemoteSources, $remoteSources);
                $this->assertEquals($expectedLocalSources, $localSources);
                $this->assertEquals($expectedStylesheetUrls, $stylesheetUrls);

                return true;
            })
            ->andReturn($expectedReturnedSourceMap);

        return $sourceStorage;
    }

    private function createSourceStorageStoreWebPageExpectation(
        MockInterface $sourceStorageMock,
        string $expectedWebPageContent,
        SourceMap $expectedLocalSources,
        SourceMap $expectedReturnedSourceMap
    ) {
        $sourceStorageMock
            ->shouldReceive('storeWebPage')
            ->withArgs(function (
                WebPage $webPage,
                SourceMap $localSources
            ) use (
                $expectedWebPageContent,
                $expectedLocalSources
            ) {
                $this->assertEquals($expectedWebPageContent, $webPage->getContent());
                $this->assertEquals($expectedLocalSources, $localSources);

                return true;
            })
            ->andReturn($expectedReturnedSourceMap);

        return $sourceStorageMock;
    }

    /**
     * @return MockInterface|CommandExecutor
     */
    private function createCommandExecutor(array $calls)
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
