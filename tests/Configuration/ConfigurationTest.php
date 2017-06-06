<?php

namespace webignition\Tests\CssValidatorWrapper\Configuration;

use GuzzleHttp\Client as HttpClient;
use Mockery\MockInterface;
use PHPUnit_Framework_TestCase;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider clearFlagDataProvider
     *
     * @param $flag
     */
    public function testClearFlag($flag)
    {
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_FLAGS => Flags::getValidValues()
        ]);

        $this->assertTrue($configuration->hasFlag($flag));
        $configuration->clearFlag($flag);

        $this->assertFalse($configuration->hasFlag($flag));
    }

    /**
     * @return array
     */
    public function clearFlagDataProvider()
    {
        $testData = [];

        foreach (Flags::getValidValues() as $flag) {
            $testData[] = [
                'flag' => $flag
            ];
        }

        return $testData;
    }

    /**
     * @dataProvider invalidFlagDataProvider
     *
     * @param string $flag
     */
    public function testSetInvalidFlag($flag)
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Invalid flag, must be one of [ignore-warnings, ignore-false-background-data-url-messages]',
            2
        );

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_FLAGS => [
                $flag
            ]
        ]);
    }

    /**
     * @return array
     */
    public function invalidFlagDataProvider()
    {
        return [
            'foo' => [
                'flag' => 'foo',
            ],
            'bar' => [
                'flag' => 'bar',
            ],
        ];
    }

    public function testSetGetContentToValidate()
    {
        $contentToValidate = 'foo';

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_CONTENT_TO_VALIDATE => $contentToValidate,
        ]);

        $this->assertEquals($configuration->getContentToValidate(), $contentToValidate);
    }

    /**
     * @dataProvider setGetDomainsToIgnoreDataProvider
     *
     * @param string[] $domainsToIgnore
     * @param string[] $expectedDomainsToIgnore
     */
    public function testSetGetDomainsToIgnore($domainsToIgnore, $expectedDomainsToIgnore)
    {
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_DOMAINS_TO_IGNORE => $domainsToIgnore,
        ]);

        $this->assertEquals($expectedDomainsToIgnore, $configuration->getDomainsToIgnore());
    }

    /**
     * @return array
     */
    public function setGetDomainsToIgnoreDataProvider()
    {
        return [
            'empty set' => [
                'domainsToIgnore' => [],
                'expectedDomainsToIgnore' => [],
            ],
            'non-empty set' => [
                'domainsToIgnore' => [
                    'foo',
                    'bar',
                ],
                'expectedDomainsToIgnore' => [
                    'foo',
                    'bar',
                ],
            ],
        ];
    }

    public function testGetExecutableCommandWithoutSettingUrlToValidate()
    {
        $configuration = new Configuration([]);

        $this->setExpectedException(\InvalidArgumentException::class, 'URL to validate has not been set', 2);
        $configuration->getExecutableCommand();
    }

    /**
     * @dataProvider getExecutableCommandDataProvider
     *
     * @param Configuration $configuration
     * @param string $expectedExecutableCommand
     */
    public function testGetExecutableCommand(Configuration $configuration, $expectedExecutableCommand)
    {
        $this->assertEquals($expectedExecutableCommand, $configuration->getExecutableCommand());
    }

    /**
     * @return array
     */
    public function getExecutableCommandDataProvider()
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

    public function testGetWebResourceService()
    {
        $httpClient = new HttpClient();

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ]);

        $webResourceService = $configuration->getWebResourceService();

        $this->assertEquals(
            [
                'text/html' => WebPage::class,
                'text/css' => WebResource::class
            ],
            $webResourceService->getConfiguration()->getContentTypeWebResourceMap()
        );

        $this->assertEquals($httpClient, $webResourceService->getConfiguration()->getHttpClient());
    }

    /**
     * @dataProvider hasContentToValidateDataProvider
     *
     * @param string $content
     * @param bool $expectedHasContentToValidate
     */
    public function testHasContentToValidate($content, $expectedHasContentToValidate)
    {
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_CONTENT_TO_VALIDATE => $content,
        ]);

        $this->assertEquals($expectedHasContentToValidate, $configuration->hasContentToValidate());
    }

    /**
     * @return array
     */
    public function hasContentToValidateDataProvider()
    {
        return [
            'null' => [
                'content' => null,
                'expectedHasContentToValidate' => false,
            ],
            'empty string' => [
                'content' => '',
                'expectedHasContentToValidate' => true,
            ],
            'non-empty string' => [
                'content' => 'foo',
                'expectedHasContentToValidate' => true,
            ],
        ];
    }

    public function testGetDefaultHttpClient()
    {
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
        ]);

        $this->assertInstanceOf(HttpClient::class, $configuration->getHttpClient());
    }

    public function testSetGetHttpClient()
    {
        /* @var $httpClient MockInterface|HttpClient */
        $httpClient = \Mockery::mock(HttpClient::class);

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_HTTP_CLIENT => $httpClient,
        ]);

        $this->assertEquals($httpClient, $configuration->getHttpClient());
    }

    /**
     * @dataProvider invalidVendorExtensionSeverityLevelDataProvider
     *
     * @param string $vendorExtensionSeverityLevel
     */
    public function testSetInvalidVendorExtensionSeverityLevel($vendorExtensionSeverityLevel)
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Invalid severity level, must be one of [error, warn, ignore]',
            1
        );

        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL => $vendorExtensionSeverityLevel,
        ]);
    }

    /**
     * @return array
     */
    public function invalidVendorExtensionSeverityLevelDataProvider()
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
        $vendorExtensionSeverityLevel,
        $expectedVendorExtensionSeverityLevel
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

    /**
     * @return array
     */
    public function vendorExtensionSeverityLevelDataProvider()
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

    /**
     * @dataProvider hasDomainsToIgnoreDataProvider
     *
     * @param string[] $domainsToIgnore
     * @param bool $expectedHasDomainsToIgnore
     */
    public function testHasDomainsToIgnore($domainsToIgnore, $expectedHasDomainsToIgnore)
    {
        $configuration = new Configuration([
            Configuration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            Configuration::CONFIG_KEY_DOMAINS_TO_IGNORE => $domainsToIgnore,
        ]);

        $this->assertEquals($expectedHasDomainsToIgnore, $configuration->hasDomainsToIgnore());
    }

    /**
     * @return array
     */
    public function hasDomainsToIgnoreDataProvider()
    {
        return [
            'null' => [
                'domainsToIgnore' => null,
                'expectedHasDomainsToIgnore' => false,
            ],
            'empty' => [
                'domainsToIgnore' => [],
                'expectedHasDomainsToIgnore' => false,
            ],
            'non-empty' => [
                'domainsToIgnore' => ['foo'],
                'expectedHasDomainsToIgnore' => true,
            ],
        ];
    }
}
