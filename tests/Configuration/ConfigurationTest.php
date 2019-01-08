<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Configuration;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        string $javaExecutablePath,
        string $cssValidatorJarPath,
        string $vendorExtensionSeverityLevel
    ) {
        $configuration = new Configuration($javaExecutablePath, $cssValidatorJarPath, $vendorExtensionSeverityLevel);

        $this->assertEquals($javaExecutablePath, $configuration->getJavaExecutablePath());
        $this->assertEquals($cssValidatorJarPath, $configuration->getCssValidatorJarPath());
        $this->assertEquals($vendorExtensionSeverityLevel, $configuration->getVendorExtensionSeverityLevel());
    }

    public function createDataProvider()
    {
        return [
            'default' => [
                'javaExecutablePath' => '/bin/java',
                'cssValidatorJarPath' => 'css-validator.jar',
                'vendorExtensionSeverityLevel' => VendorExtensionSeverityLevel::LEVEL_WARN,
            ],
        ];
    }
}
