<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests;

use phpmock\mockery\PHPMockery;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\CommandExecutor;

class CommandExecutorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CommandExecutor
     */
    private $commandExecutor;

    protected function setUp()
    {
        parent::setUp();

        $this->commandExecutor = new CommandExecutor(new OutputParser());
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        string $cssValidatorFixture,
        OutputParserConfiguration $outputParserConfiguration,
        int $expectedWarningCount,
        int $expectedErrorCount
    ) {
        $this->setCssValidatorRawOutput($this->loadCssValidatorRawOutputFixture($cssValidatorFixture));

        $command = 'non-blank string';

        /* @var ValidationOutput $output */
        $output = $this->commandExecutor->execute($command, $outputParserConfiguration);

        $this->assertInstanceOf(ValidationOutput::class, $output);

        $messageList = $output->getMessages();
        $this->assertEquals($expectedWarningCount, $messageList->getWarningCount());
        $this->assertEquals($expectedErrorCount, $messageList->getErrorCount());
    }

    public function executeDataProvider(): array
    {
        return [
            'ignore false image data url messages' => [
                'cssValidatorFixture' => 'incorrect-data-url-background-image-errors',
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_FALSE_DATA_URL_MESSAGES => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore warnings' => [
                'cssValidatorFixture' => 'single-warning',
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension issues:warn and ignore warnings' => [
                'cssValidatorFixture' => 'vendor-specific-at-rules',
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                    OutputParserConfiguration::KEY_REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension warnings' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_WARNINGS => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension errors' => [
                'cssValidatorFixture' => 'three-vendor-extension-errors',
                'outputParserConfiguration' => new OutputParserConfiguration([
                    OutputParserConfiguration::KEY_IGNORE_VENDOR_EXTENSION_ISSUES => true,
                ]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: warn' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'outputParserConfiguration' => new OutputParserConfiguration(),
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'cssValidatorFixture' => 'three-vendor-extension-errors',
                'outputParserConfiguration' => new OutputParserConfiguration([]),
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
                'cssValidatorFixture' => 'vendor-specific-at-rules',
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

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
