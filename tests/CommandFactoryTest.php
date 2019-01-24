<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests;

use webignition\CssValidatorWrapper\CommandFactory;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel;

class CommandFactoryTest extends \PHPUnit\Framework\TestCase
{
    const JAVA_EXECUTABLE_PATH = '/usr/bin/java';
    const CSS_VALIDATOR_JAR_PATH = '/usr/share/css-validator.jar';

    /**
     * @var CommandFactory
     */
    private $commandFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->commandFactory = new CommandFactory(
            self::JAVA_EXECUTABLE_PATH,
            self::CSS_VALIDATOR_JAR_PATH
        );
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        string $url,
        string $vendorExtensionSeverityLevel,
        string $expectedExecutableCommand
    ) {
        $this->assertEquals(
            $expectedExecutableCommand,
            $this->commandFactory->create(
                $url,
                $vendorExtensionSeverityLevel
            )
        );
    }

    public function createDataProvider(): array
    {
        return [
            'vendorExtensionSeverityLevel=warn' => [
                'url' => 'http://example.com/1',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedExecutableCommand' => sprintf(
                    '%s -jar %s -output ucn -vextwarning true "http://example.com/1" 2>&1',
                    self::JAVA_EXECUTABLE_PATH,
                    self::CSS_VALIDATOR_JAR_PATH
                ),
            ],
            'vendorExtensionSeverityLevel=ignore' => [
                'url' => 'http://example.com/2',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                'expectedExecutableCommand' => sprintf(
                    '%s -jar %s -output ucn -vextwarning false "http://example.com/2" 2>&1',
                    self::JAVA_EXECUTABLE_PATH,
                    self::CSS_VALIDATOR_JAR_PATH
                ),
            ],
            'vendorExtensionSeverityLevel=error' => [
                'url' => 'http://example.com/3',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'expectedExecutableCommand' => sprintf(
                    '%s -jar %s -output ucn -vextwarning false "http://example.com/3" 2>&1',
                    self::JAVA_EXECUTABLE_PATH,
                    self::CSS_VALIDATOR_JAR_PATH
                ),
            ],
            'double quotes in url' => [
                'url' => 'http://"example".com/',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedExecutableCommand' => sprintf(
                    '%s -jar %s -output ucn -vextwarning true "http://\"example\".com/" 2>&1',
                    self::JAVA_EXECUTABLE_PATH,
                    self::CSS_VALIDATOR_JAR_PATH
                ),
            ],
        ];
    }
}
