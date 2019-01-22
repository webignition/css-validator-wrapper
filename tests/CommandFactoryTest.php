<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests;

use webignition\CssValidatorWrapper\CommandFactory;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel;

class CommandFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CommandFactory
     */
    private $commandFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->commandFactory = new CommandFactory();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreateFoo(
        string $url,
        string $javaExecutablePath,
        string $cssValidatorJarPath,
        string $vendorExtensionSeverityLevel,
        string $expectedExecutableCommand
    ) {
        $this->assertEquals(
            $expectedExecutableCommand,
            $this->commandFactory->create(
                $url,
                $javaExecutablePath,
                $cssValidatorJarPath,
                $vendorExtensionSeverityLevel
            )
        );
    }

    public function createDataProvider(): array
    {
        return [
            'vendorExtensionSeverityLevel=warn' => [
                'url' => 'http://example.com/1',
                'javaExecutablePath' => 'java',
                'cssValidatorJarPath' => 'css-validator.jar',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/1" 2>&1',
            ],
            'vendorExtensionSeverityLevel=ignore' => [
                'url' => 'http://example.com/2',
                'javaExecutablePath' => 'java',
                'cssValidatorJarPath' => '/usr/bin/css-validator.jar',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                'expectedExecutableCommand' =>
                    'java -jar /usr/bin/css-validator.jar -output ucn -vextwarning false "http://example.com/2" 2>&1',
            ],
            'vendorExtensionSeverityLevel=error' => [
                'url' => 'http://example.com/3',
                'javaExecutablePath' => '/bin/java',
                'cssValidatorJarPath' => 'css-validator.jar',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'expectedExecutableCommand' =>
                    '/bin/java -jar css-validator.jar -output ucn -vextwarning false "http://example.com/3" 2>&1',
            ],
            'double quotes in url' => [
                'url' => 'http://"example".com/',
                'javaExecutablePath' => 'java',
                'cssValidatorJarPath' => 'css-validator.jar',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://\"example\".com/" 2>&1',
            ],
        ];
    }
}
