<?php

namespace webignition\CssValidatorWrapper\Configuration;

class Configuration
{
    /**
     * @var string
     */
    private $javaExecutablePath = null;

    /**
     * @var string
     */
    private $cssValidatorJarPath = null;

    /**
     * @var string
     */
    private $vendorExtensionSeverityLevel = null;

    public function __construct(
        string $javaExecutablePath,
        string $cssValidatorJarPath,
        string $vendorExtensionSeverityLevel
    ) {
        $this->javaExecutablePath = $javaExecutablePath;
        $this->cssValidatorJarPath = $cssValidatorJarPath;
        $this->vendorExtensionSeverityLevel = $vendorExtensionSeverityLevel;
    }

    public function getJavaExecutablePath(): string
    {
        return $this->javaExecutablePath;
    }

    public function getCssValidatorJarPath(): string
    {
        return $this->cssValidatorJarPath;
    }

    public function getVendorExtensionSeverityLevel(): string
    {
        return $this->vendorExtensionSeverityLevel;
    }
}
