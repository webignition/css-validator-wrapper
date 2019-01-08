<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use phpmock\mockery\PHPMockery;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\CommandFactory;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper;

class WrapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        string $cssValidatorRawOutput,
        string $vendorExtensionSeverityLevel,
        OutputParserConfiguration $outputParserConfiguration,
        int $expectedWarningCount,
        int $expectedErrorCount,
        array $expectedErrorCountByUrl = []
    ) {
        $javaExecutablePath = '/usr/bin/java';
        $cssValidatorJarPath = 'css-validator.jar';

        $wrapper = new Wrapper(
            new CommandFactory(),
            new OutputParser(),
            $javaExecutablePath,
            $cssValidatorJarPath
        );

        $this->setCssValidatorRawOutput($cssValidatorRawOutput);

        /* @var ValidationOutput $output */
        $output = $wrapper->validate(
            'http://example.com',
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

    public function validateDataProvider(): array
    {
        return [
            'ignore false image data url messages' => [
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
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('single-warning'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension issues:warn and ignore warnings' => [
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
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension errors' => [
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
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('no-messages'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: warn' => [
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'outputParserConfiguration' => new OutputParserConfiguration([]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
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

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
