<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use phpmock\mockery\PHPMockery;
use Psr\Http\Message\UriInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\CommandFactory;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\CssValidatorWrapper\SourceHandler;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\CssValidatorWrapper\SourcePreparer;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Wrapper;
use webignition\WebResource\WebPage\WebPage;

class WrapperTest extends \PHPUnit\Framework\TestCase
{
    const JAVA_EXECUTABLE_PATH = '/usr/bin/java';
    const CSS_VALIDATOR_JAR_PATH = 'css-validator.jar';

    public function testValidateUnknownSourceExceptionForWebPage()
    {
        $webPage = $this->createWebPage('http://example.com/', 'content');
        $wrapper = $this->createWrapper();

        $sourceHandler = new SourceHandler($webPage, new SourceMap());

        $this->expectException(UnknownSourceException::class);
        $this->expectExceptionCode(UnknownSourceException::CODE);
        $this->expectExceptionMessage('Unknown source "http://example.com/"');

        $wrapper->validate($sourceHandler, VendorExtensionSeverityLevel::LEVEL_WARN);
    }

    /**
     * @dataProvider validateSuccessDataProvider
     */
    public function testValidateSuccess(
        SourceMap $sourceMap,
        string $sourceFixture,
        string $sourceUrl,
        string $cssValidatorRawOutput,
        string $vendorExtensionSeverityLevel,
        OutputParserConfiguration $outputParserConfiguration,
        int $expectedWarningCount,
        int $expectedErrorCount,
        array $expectedErrorCountByUrl = []
    ) {
        $webPage = $this->createWebPage($sourceUrl, $sourceFixture);
        $wrapper = $this->createWrapper();

        $sourceHandler = new SourceHandler($webPage, $sourceMap);

        $this->setCssValidatorRawOutput($cssValidatorRawOutput);

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

    public function validateSuccessDataProvider(): array
    {
        return [
            'ignore false image data url messages' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture(
                    'incorrect-data-url-background-image-errors'
                ),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_FALSE_DATA_URL_MESSAGES => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore warnings' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('single-warning'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension issues:warn and ignore warnings' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('vendor-specific-at-rules'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                    OutputParserConfiguration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension warnings' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension errors' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_VENDOR_EXTENSION_ISSUES => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
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


            'html5 no css no linked resources' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: warn' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'outputParserConfiguration' => new OutputParserConfiguration([]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
                'sourceMap' => new SourceMap([
                    'http://example.com/' => FixtureLoader::getPath('Html/minimal-html5.html'),
                ]),
                'sourceFixture' => FixtureLoader::load('Html/minimal-html5.html'),
                'sourceUrl' => 'http://example.com/',
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('vendor-specific-at-rules'),
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

    private function loadCssValidatorRawOutputFixture(string $name): string
    {
        return file_get_contents(__DIR__ . '/Fixtures/CssValidatorOutput/' . $name . '.txt');
    }

    private function createWrapper(): Wrapper
    {
        return new Wrapper(
            new SourcePreparer(),
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

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
