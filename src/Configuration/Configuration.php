<?php

namespace webignition\CssValidatorWrapper\Configuration;

use webignition\CssValidatorOutput\Parser\Configuration as OutputParserConfiguration;

class Configuration
{
    const JAVA_JAR_FLAG = '-jar';
    const DEFAULT_JAVA_EXECUTABLE_PATH = 'java';
    const DEFAULT_CSS_VALIDATOR_JAR_PATH = 'css-validator.jar';
    const DEFAULT_OUTPUT_FORMAT = 'ucn';
    const DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL = VendorExtensionSeverityLevel::LEVEL_WARN;

    const CONFIG_KEY_JAVA_EXECUTABLE_PATH = 'java-executable-path';
    const CONFIG_KEY_CSS_VALIDATOR_JAR_PATH = 'css-validator-jar-path';
    const CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL = 'vendor-extension-severity-level';
    const CONFIG_KEY_URL_TO_VALIDATE = 'url-to-validate';
    const CONFIG_KEY_CONTENT_TO_VALIDATE = 'content-to-validate';
    const CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION = 'output-parser-configuration';

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

    /**
     * @var string
     */
    private $urlToValidate = null;

    /**
     * @var string
     */
    private $contentToValidate = null;

    /**
     * @var OutputParserConfiguration
     */
    private $outputParserConfiguration;

    public function __construct(array $values)
    {
        $this->javaExecutablePath =
            $values[self::CONFIG_KEY_JAVA_EXECUTABLE_PATH] ?? self::DEFAULT_JAVA_EXECUTABLE_PATH;

        $this->cssValidatorJarPath =
            $values[self::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH] ?? self::DEFAULT_CSS_VALIDATOR_JAR_PATH;

        $this->outputParserConfiguration =
            $values[self::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION] ?? new OutputParserConfiguration();

        $this->setVendorExtensionSeverityLevel(
            $values[self::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL] ?? self::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL
        );
        $this->setUrlToValidate($values[self::CONFIG_KEY_URL_TO_VALIDATE] ?? '');
        $this->setContentToValidate($values[self::CONFIG_KEY_CONTENT_TO_VALIDATE] ?? '');
    }

    public function getOutputParserConfiguration(): OutputParserConfiguration
    {
        return $this->outputParserConfiguration;
    }

    public function setContentToValidate(string $content)
    {
        $this->contentToValidate = $content;
    }

    public function getContentToValidate(): ?string
    {
        return $this->contentToValidate;
    }

    public function getJavaExecutablePath(): string
    {
        return $this->javaExecutablePath;
    }

    private function getCssValidatorJarPath(): string
    {
        return $this->cssValidatorJarPath;
    }

    private function getOutputFormat(): string
    {
        return self::DEFAULT_OUTPUT_FORMAT;
    }

    /**
     * @param string $vendorExtensionSeverityLevel
     * @throws \InvalidArgumentException
     */
    private function setVendorExtensionSeverityLevel(string $vendorExtensionSeverityLevel)
    {
        if (!VendorExtensionSeverityLevel::isValid($vendorExtensionSeverityLevel)) {
            throw new \InvalidArgumentException(
                'Invalid severity level, must be one of ['
                .implode(', ', VendorExtensionSeverityLevel::getValidValues())
                .']',
                1
            );
        }

        $this->vendorExtensionSeverityLevel = $vendorExtensionSeverityLevel;
    }

    public function getVendorExtensionSeverityLevel(): string
    {
        return $this->vendorExtensionSeverityLevel;
    }

    public function setUrlToValidate(string $url)
    {
        $this->urlToValidate = trim($url);
    }

    public function getUrlToValidate(): string
    {
        return $this->urlToValidate;
    }

    /**
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getExecutableCommand(): string
    {
        if (!$this->hasUrlToValidate()) {
            throw new \InvalidArgumentException('URL to validate has not been set', 2);
        }

        $commandParts = array(
            $this->getJavaExecutablePath(),
            self::JAVA_JAR_FLAG,
            $this->getCssValidatorJarPath(),
            $this->getCommandOptionsString(),
            '"'.str_replace('"', '\"', $this->getUrlToValidate()).'"',
            '2>&1'
        );

        return implode(' ', $commandParts);
    }

    public function hasUrlToValidate(): bool
    {
        return $this->getUrlToValidate() != '';
    }

    private function getCommandOptionsString(): string
    {
        $commandOptionsStrings = array();
        foreach ($this->getCommandOptions() as $key => $value) {
            $commandOptionsStrings[] = '-'.$key.' '.$value;
        }

        return implode(' ', $commandOptionsStrings);
    }

    private function getCommandOptions(): array
    {
        $commandOptions = array(
            'output' => $this->getOutputFormat(),
        );

        if ($this->getVendorExtensionSeverityLevel() == VendorExtensionSeverityLevel::LEVEL_WARN) {
            $commandOptions['vextwarning'] = 'true';
        } else {
            $commandOptions['vextwarning'] = 'false';
        }

        return $commandOptions;
    }
}
