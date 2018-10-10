<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testSetGetContentToValidate()
    {
        $contentToValidate = 'foo';

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_CONTENT_TO_VALIDATE => $contentToValidate,
        ]);

        $this->assertEquals($configuration->getContentToValidate(), $contentToValidate);
    }

    public function testGetExecutableCommandWithoutSettingUrlToValidate()
    {
        $configuration = new Configuration([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL to validate has not been set');
        $this->expectExceptionCode(2);

        $configuration->createExecutableCommand();
    }

    /**
     * @dataProvider createExecutableCommandDataProvider
     *
     * @param Configuration $configuration
     * @param string $expectedExecutableCommand
     */
    public function testCreateExecutableCommand(Configuration $configuration, string $expectedExecutableCommand)
    {
        $this->assertEquals($expectedExecutableCommand, $configuration->createExecutableCommand());
    }

    public function createExecutableCommandDataProvider(): array
    {
        return [
            'use default' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
                ]),
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'set to default' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
                    Configuration::CONFIG_KEY_JAVA_EXECUTABLE_PATH => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                    Configuration::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH => Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH,
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        Configuration::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL,
                ]),
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'non-default url to validate' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://foo.example.com/',
                ]),
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://foo.example.com/" 2>&1',
            ],
            'non-default java executable path' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
                    Configuration::CONFIG_KEY_JAVA_EXECUTABLE_PATH => '/foo/java',
                ]),
                'expectedExecutableCommand' =>
                    '/foo/java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'non-default css validator jar path' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
                    Configuration::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH => '/foo/css-validator-foo.jar',
                ]),
                'expectedExecutableCommand' =>
                    'java -jar /foo/css-validator-foo.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'vendor extension severity level: warn' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                ]),
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'vendor extension severity level: ignore' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_IGNORE,
                ]),
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning false "http://example.com/" 2>&1',
            ],
            'vendor extension severity level: error' => [
                'configuration' => new Configuration([
                    Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
                    Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_ERROR,
                ]),
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning false "http://example.com/" 2>&1',
            ],
        ];
    }

    /**
     * @dataProvider invalidVendorExtensionSeverityLevelDataProvider
     *
     * @param string $vendorExtensionSeverityLevel
     */
    public function testSetInvalidVendorExtensionSeverityLevel(string $vendorExtensionSeverityLevel)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid severity level, must be one of [error, warn, ignore]');
        $this->expectExceptionCode(1);

        new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL => $vendorExtensionSeverityLevel,
        ]);
    }

    public function invalidVendorExtensionSeverityLevelDataProvider(): array
    {
        return [
            'foo' => [
                Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL => 'foo',
            ],
            'bar' => [
                Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL => 'bar',
            ],
        ];
    }

    /**
     * @dataProvider vendorExtensionSeverityLevelDataProvider
     *
     * @param string $vendorExtensionSeverityLevel
     * @param string $expectedVendorExtensionSeverityLevel
     */
    public function testSetVendorExtensionSeverityLevel(
        string $vendorExtensionSeverityLevel,
        string $expectedVendorExtensionSeverityLevel
    ) {
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL => $vendorExtensionSeverityLevel,
        ]);

        $this->assertEquals(
            $expectedVendorExtensionSeverityLevel,
            $configuration->getVendorExtensionSeverityLevel()
        );
    }

    public function vendorExtensionSeverityLevelDataProvider(): array
    {
        $testData = [];

        foreach (VendorExtensionSeverityLevel::getValidValues() as $validValue) {
            $testData[] = [
                Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL => $validValue,
                'expectedVendorExtensionSeverityLevel' => $validValue,
            ];
        }

        return $testData;
    }
}
