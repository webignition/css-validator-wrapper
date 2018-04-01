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

    /**
     * @param array $configurationValues
     */
    public function __construct($configurationValues)
    {
        if (!isset($configurationValues[self::CONFIG_KEY_JAVA_EXECUTABLE_PATH])) {
            $configurationValues[self::CONFIG_KEY_JAVA_EXECUTABLE_PATH] = self::DEFAULT_JAVA_EXECUTABLE_PATH;
        }

        if (!isset($configurationValues[self::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH])) {
            $configurationValues[self::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH] = self::DEFAULT_CSS_VALIDATOR_JAR_PATH;
        }

        if (!isset($configurationValues[self::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION])) {
            $configurationValues[self::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION] = new OutputParserConfiguration();
        }

        $this->javaExecutablePath = $configurationValues[self::CONFIG_KEY_JAVA_EXECUTABLE_PATH];
        $this->cssValidatorJarPath = $configurationValues[self::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH];
        $this->outputParserConfiguration = $configurationValues[self::CONFIG_KEY_OUTPUT_PARSER_CONFIGURATION];

        if (array_key_exists(self::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL, $configurationValues)) {
            $this->setVendorExtensionSeverityLevel(
                $configurationValues[self::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL]
            );
        }

        if (array_key_exists(self::CONFIG_KEY_URL_TO_VALIDATE, $configurationValues)) {
            $this->setUrlToValidate($configurationValues[self::CONFIG_KEY_URL_TO_VALIDATE]);
        }

        if (array_key_exists(self::CONFIG_KEY_CONTENT_TO_VALIDATE, $configurationValues)) {
            $this->setContentToValidate($configurationValues[self::CONFIG_KEY_CONTENT_TO_VALIDATE]);
        }
    }

    /**
     * @return mixed|OutputParserConfiguration
     */
    public function getOutputParserConfiguration()
    {
        return $this->outputParserConfiguration;
    }

    /**
     * @param string $content
     */
    public function setContentToValidate($content)
    {
        $this->contentToValidate = $content;
    }

    /**
     * @return string
     */
    public function getContentToValidate()
    {
        return $this->contentToValidate;
    }

    /**
     * @return string
     */
    public function getJavaExecutablePath()
    {
        return (is_null($this->javaExecutablePath)) ? self::DEFAULT_JAVA_EXECUTABLE_PATH : $this->javaExecutablePath;
    }

    /**
     * @return string
     */
    private function getCssValidatorJarPath()
    {
        return is_null($this->cssValidatorJarPath)
            ? self::DEFAULT_CSS_VALIDATOR_JAR_PATH
            : $this->cssValidatorJarPath;
    }

    /**
     * @return string
     */
    private function getOutputFormat()
    {
        return self::DEFAULT_OUTPUT_FORMAT;
    }

    /**
     * @param string $vendorExtensionSeverityLevel
     * @throws \InvalidArgumentException
     */
    private function setVendorExtensionSeverityLevel($vendorExtensionSeverityLevel)
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

    /**
     * @return string
     */
    public function getVendorExtensionSeverityLevel()
    {
        return is_null($this->vendorExtensionSeverityLevel)
            ? self::DEFAULT_VENDOR_EXTENSION_SEVERITY_LEVEL
            : $this->vendorExtensionSeverityLevel;
    }

    /**
     * @param string $url
     */
    public function setUrlToValidate($url)
    {
        $this->urlToValidate = trim($url);
    }


    /**
     * @return string
     */
    public function getUrlToValidate()
    {
        return is_null($this->urlToValidate)
            ? ''
            : $this->urlToValidate;
    }

    /**
     * @return string
     */
    public function getExecutableCommand()
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

    /**
     * @return bool
     */
    public function hasUrlToValidate()
    {
        return $this->getUrlToValidate() != '';
    }

    /**
     * @return string
     */
    private function getCommandOptionsString()
    {
        $commandOptionsStrings = array();
        foreach ($this->getCommandOptions() as $key => $value) {
            $commandOptionsStrings[] = '-'.$key.' '.$value;
        }

        return implode(' ', $commandOptionsStrings);
    }

    /**
     * @return array
     */
    private function getCommandOptions()
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
