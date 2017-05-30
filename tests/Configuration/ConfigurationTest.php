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
     * @var Configuration
     */
    private $configuration;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->configuration = new Configuration();
    }

    /**
     * @dataProvider clearFlagDataProvider
     *
     * @param $flag
     */
    public function testClearFlag($flag)
    {
        foreach (Flags::getValidValues() as $flagToSet) {
            $this->configuration->setFlag($flagToSet);
        }

        $this->assertTrue($this->configuration->hasFlag($flag));
        $this->configuration->clearFlag($flag);

        $this->assertFalse($this->configuration->hasFlag($flag));
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
        $this->configuration->setFlag($flag);
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
        $this->assertEmpty($this->configuration->getContentToValidate());
        $contentToValidate = 'foo';

        $this->configuration->setContentToValidate($contentToValidate);

        $this->assertEquals($this->configuration->getContentToValidate(), $contentToValidate);
    }

    /**
     * @dataProvider setGetDomainsToIgnoreDataProvider
     *
     * @param string[] $domainsToIgnore
     * @param string[] $expectedDomainsToIgnore
     */
    public function testSetGetDomainsToIgnore($domainsToIgnore, $expectedDomainsToIgnore)
    {
        $this->configuration->setDomainsToIgnore($domainsToIgnore);

        $this->assertEquals($expectedDomainsToIgnore, $this->configuration->getDomainsToIgnore());
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
        $this->setExpectedException(\InvalidArgumentException::class, 'URL to validate has not been set', 2);
        $this->configuration->getExecutableCommand();
    }

    /**
     * @dataProvider getExecutableCommandDataProvider
     *
     * @param string $urlToValidate
     * @param string $javaExecutablePath
     * @param string $cssValidatorJarPath
     * @param string $vendorExtensionSeverityLevel
     * @param string $expectedExecutableCommand
     */
    public function testGetExecutableCommand(
        $urlToValidate,
        $javaExecutablePath,
        $cssValidatorJarPath,
        $vendorExtensionSeverityLevel,
        $expectedExecutableCommand
    ) {
        $this->configuration->setUrlToValidate($urlToValidate);

        if (!empty($javaExecutablePath)) {
            $this->configuration->setJavaExecutablePath($javaExecutablePath);
        }

        if (!empty($cssValidatorJarPath)) {
            $this->configuration->setCssValidatorJarPath($cssValidatorJarPath);
        }

        if (!empty($vendorExtensionSeverityLevel)) {
            $this->configuration->setVendorExtensionSeverityLevel($vendorExtensionSeverityLevel);
        }

        $this->assertEquals($expectedExecutableCommand, $this->configuration->getExecutableCommand());
    }

    /**
     * @return array
     */
    public function getExecutableCommandDataProvider()
    {
        return [
            'use default' => [
                'urlToValidate' => 'http://example.com/',
                'javaExecutablePath' => null,
                'cssValidatorJarPath' => null,
                'vendorExtensionSeverityLevel' => null,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'set to default' => [
                'urlToValidate' => 'http://example.com/',
                'javaExecutablePath' => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                'cssValidatorJarPath' => Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH,
                'vendorExtensionSeverityLevel' => Configuration::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'non-default url to validate' => [
                'urlToValidate' => 'http://foo.example.com/',
                'javaExecutablePath' => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                'cssValidatorJarPath' => Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH,
                'vendorExtensionSeverityLevel' => Configuration::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://foo.example.com/" 2>&1',
            ],
            'non-default java executable path' => [
                'urlToValidate' => 'http://example.com/',
                'javaExecutablePath' => '/foo/java',
                'cssValidatorJarPath' => Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH,
                'vendorExtensionSeverityLevel' => Configuration::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL,
                'expectedExecutableCommand' =>
                    '/foo/java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'non-default css validator jar path' => [
                'urlToValidate' => 'http://example.com/',
                'javaExecutablePath' => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                'cssValidatorJarPath' => '/foo/css-validator-foo.jar',
                'vendorExtensionSeverityLevel' => Configuration::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL,
                'expectedExecutableCommand' =>
                    'java -jar /foo/css-validator-foo.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'vendor extension severity level: warn' => [
                'urlToValidate' => 'http://example.com/',
                'javaExecutablePath' => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                'cssValidatorJarPath' => Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH,
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning true "http://example.com/" 2>&1',
            ],
            'vendor extension severity level: ignore' => [
                'urlToValidate' => 'http://example.com/',
                'javaExecutablePath' => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                'cssValidatorJarPath' => Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH,
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning false "http://example.com/" 2>&1',
            ],
            'vendor extension severity level: error' => [
                'urlToValidate' => 'http://example.com/',
                'javaExecutablePath' => Configuration::DEFAULT_JAVA_EXECUTABLE_PATH,
                'cssValidatorJarPath' => Configuration::DEFAULT_CSS_VALIDATOR_JAR_PATH,
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                'expectedExecutableCommand' =>
                    'java -jar css-validator.jar -output ucn -vextwarning false "http://example.com/" 2>&1',
            ],
        ];
    }

    public function testGetWebResourceService()
    {
        $webResourceService = $this->configuration->getWebResourceService();

        $this->assertEquals(
            [
                'text/html' => WebPage::class,
                'text/css' => WebResource::class
            ],
            $webResourceService->getConfiguration()->getContentTypeWebResourceMap()
        );
    }

    /**
     * @dataProvider hasContentToValidateDataProvider
     *
     * @param string $content
     * @param bool $expectedHasContentToValidate
     */
    public function testHasContentToValidate($content, $expectedHasContentToValidate)
    {
        $this->configuration->setContentToValidate($content);
        $this->assertEquals($expectedHasContentToValidate, $this->configuration->hasContentToValidate());
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
        $this->assertInstanceOf(HttpClient::class, $this->configuration->getHttpClient());
    }

    public function testSetGetHttpClient()
    {
        /* @var $httpClient MockInterface|HttpClient */
        $httpClient = \Mockery::mock(HttpClient::class);

        $this->configuration->setHttpClient($httpClient);

        $this->assertEquals($httpClient, $this->configuration->getHttpClient());
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
        $this->configuration->setVendorExtensionSeverityLevel($vendorExtensionSeverityLevel);
    }

    /**
     * @return array
     */
    public function invalidVendorExtensionSeverityLevelDataProvider()
    {
        return [
            'foo' => [
                'vendorExtensionSeverityLevel' => 'foo',
            ],
            'bar' => [
                'vendorExtensionSeverityLevel' => 'bar',
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
        $this->configuration->setVendorExtensionSeverityLevel($vendorExtensionSeverityLevel);
        $this->assertEquals(
            $expectedVendorExtensionSeverityLevel,
            $this->configuration->getVendorExtensionSeverityLevel()
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
                'vendorExtensionSeverityLevel' => $validValue,
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
        $this->configuration->setDomainsToIgnore($domainsToIgnore);
        $this->assertEquals($expectedHasDomainsToIgnore, $this->configuration->hasDomainsToIgnore());
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
