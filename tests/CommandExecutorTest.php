<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests;

use phpmock\mockery\PHPMockery;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\Flags;
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
        int $outputParserFlags,
        int $expectedWarningCount,
        int $expectedErrorCount
    ) {
        $this->setCssValidatorRawOutput($this->loadCssValidatorRawOutputFixture($cssValidatorFixture));

        $command = 'non-blank string';

        /* @var ValidationOutput $output */
        $output = $this->commandExecutor->execute($command, $outputParserFlags);

        $this->assertInstanceOf(ValidationOutput::class, $output);

        if ($output instanceof ValidationOutput) {
            $messageList = $output->getMessages();
            $this->assertEquals($expectedWarningCount, $messageList->getWarningCount());
            $this->assertEquals($expectedErrorCount, $messageList->getErrorCount());
        }
    }

    public function executeDataProvider(): array
    {
        return [
            'ignore false image data url messages' => [
                'cssValidatorFixture' => 'incorrect-data-url-background-image-errors',
                'outputParserFlags' => Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore warnings' => [
                'cssValidatorFixture' => 'single-warning',
                'outputParserFlags' => Flags::IGNORE_WARNINGS,
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension issues:warn and ignore warnings' => [
                'cssValidatorFixture' => 'vendor-specific-at-rules',
                'outputParserFlags' => Flags::IGNORE_WARNINGS | Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS,
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension warnings' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'outputParserFlags' => Flags::IGNORE_WARNINGS,
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'ignore vendor extension errors' => [
                'cssValidatorFixture' => 'three-vendor-extension-errors',
                'outputParserFlags' => Flags::IGNORE_VENDOR_EXTENSION_ISSUES,
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: default' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'outputParserFlags' => Flags::NONE,
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: warn' => [
                'cssValidatorFixture' => 'three-vendor-extension-warnings',
                'outputParserFlags' => Flags::NONE,
                'expectedWarningCount' => 3,
                'expectedErrorCount' => 0,
            ],
            'vendor extension warnings: error' => [
                'cssValidatorFixture' => 'three-vendor-extension-errors',
                'outputParserFlags' => Flags::NONE,
                'expectedWarningCount' => 0,
                'expectedErrorCount' => 3,
            ],
            'vendor extension warnings: warn, with at-rule errors that should be warnings' => [
                'cssValidatorFixture' => 'vendor-specific-at-rules',
                'outputParserFlags' => Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS,
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
        $fixtureContent = (string) file_get_contents(__DIR__ . '/Fixtures/CssValidatorOutput/' . $name . '.txt');

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
