<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper;

class WrapperTest extends \PHPUnit\Framework\TestCase
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

    public function testFoo()
    {
        $this->assertTrue(true);
    }

    /**
     * @dataProvider validateSuccessDataProvider
     */
    public function testValidateSuccess(
        string $cssValidatorRawOutput,
        array $configurationValues,
        int $expectedWarningCount,
        int $expectedErrorCount,
        array $expectedErrorCountByUrl = []
    ) {
        $this->setCssValidatorRawOutput($cssValidatorRawOutput);

        $configuration = new Configuration(array_merge([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ], $configurationValues));

        /* @var ValidationOutput $output */
        $output = $this->wrapper->validate($configuration);
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
                'configurationValues' => [],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'configurationValues' => [],
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: warn' => [
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-warnings'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                ],
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'cssValidatorRawOutput' => $this->loadCssValidatorRawOutputFixture('three-vendor-extension-errors'),
                'configurationValues' => [
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_ERROR,
                ],
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
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
