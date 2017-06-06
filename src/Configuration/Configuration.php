<?php

namespace webignition\CssValidatorWrapper\Configuration;

use GuzzleHttp\Client as HttpClient;
use webignition\WebResource\Service\Configuration as WebResourceServiceConfiguration;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;

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
    const CONFIG_KEY_FLAGS = 'flags';
    const CONFIG_KEY_DOMAINS_TO_IGNORE = 'domains-to-ignore';
    const CONFIG_KEY_HTTP_CLIENT = 'http-client';

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
     *
     * @var string
     */
    private $contentToValidate = null;

    /**
     * @var array
     */
    private $flags = array();

    /**
     * @var string[]
     */
    private $domainsToIgnore = array();

    /**
     * @var WebResourceService
     */
    private $webResourceService;

    /**
     * @var HttpClient
     */
    private $httpClient;

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

        $this->setJavaExecutablePath($configurationValues[self::CONFIG_KEY_JAVA_EXECUTABLE_PATH]);
        $this->setCssValidatorJarPath($configurationValues[self::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH]);

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

        if (array_key_exists(self::CONFIG_KEY_FLAGS, $configurationValues)) {
            foreach ($configurationValues[self::CONFIG_KEY_FLAGS] as $flag) {
                $this->setFlag($flag);
            }
        }

        if (array_key_exists(self::CONFIG_KEY_DOMAINS_TO_IGNORE, $configurationValues)) {
            $this->setDomainsToIgnore($configurationValues[self::CONFIG_KEY_DOMAINS_TO_IGNORE]);
        }

        if (array_key_exists(self::CONFIG_KEY_HTTP_CLIENT, $configurationValues)) {
            $this->setHttpClient($configurationValues[self::CONFIG_KEY_HTTP_CLIENT]);
        }
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
     * @return boolean
     */
    public function hasContentToValidate()
    {
        return is_string($this->contentToValidate);
    }

    /**
     * @return WebResourceService
     */
    public function getWebResourceService()
    {
        if (is_null($this->webResourceService)) {
            $webResourceServiceConfiguration = new WebResourceServiceConfiguration([
                WebResourceServiceConfiguration::CONFIG_KEY_CONTENT_TYPE_WEB_RESOURCE_MAP => [
                    'text/html' => WebPage::class,
                    'text/css' => WebResource::class
                ],
                WebResourceServiceConfiguration::CONFIG_ALLOW_UNKNOWN_RESOURCE_TYPES => false,
                WebResourceServiceConfiguration::CONFIG_KEY_HTTP_CLIENT => $this->getHttpClient(),
            ]);

            $this->webResourceService = new WebResourceService();
            $this->webResourceService->setConfiguration($webResourceServiceConfiguration);
        }

        return $this->webResourceService;
    }

    /**
     * @param HttpClient $httpClient
     */
    private function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient();
        }

        return $this->httpClient;
    }

    /**
     * @param string $javaExecutablePath
     */
    private function setJavaExecutablePath($javaExecutablePath)
    {
        $this->javaExecutablePath = $javaExecutablePath;
    }

    /**
     * @return string
     */
    public function getJavaExecutablePath()
    {
        return (is_null($this->javaExecutablePath)) ? self::DEFAULT_JAVA_EXECUTABLE_PATH : $this->javaExecutablePath;
    }

    /**
     * @param string $cssValidatorJarPath
     */
    private function setCssValidatorJarPath($cssValidatorJarPath)
    {
        $this->cssValidatorJarPath = $cssValidatorJarPath;
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

    /**
     * @param string $flag
     * @throws \InvalidArgumentException
     */
    private function setFlag($flag)
    {
        if (!Flags::isValid($flag)) {
            throw new \InvalidArgumentException(
                'Invalid flag, must be one of ['
                .implode(', ', Flags::getValidValues())
                .']',
                2
            );
        }

        $this->flags[$flag] = true;
    }

    /**
     * @param string $flag
     *
     * @return boolean
     */
    public function hasFlag($flag)
    {
        return isset($this->flags[$flag]);
    }

    /**
     * @param string $flag
     *
     * @return self
     */
    public function clearFlag($flag)
    {
        if ($this->hasFlag($flag)) {
            unset($this->flags[$flag]);
        }

        return $this;
    }

    /**
     * @param string[] $domainsToIgnore
     */
    private function setDomainsToIgnore($domainsToIgnore)
    {
        $this->domainsToIgnore = $domainsToIgnore;
    }

    /**
     * @return string[]
     */
    public function getDomainsToIgnore()
    {
        return $this->domainsToIgnore;
    }

    /**
     * @return boolean
     */
    public function hasDomainsToIgnore()
    {
        return !empty($this->getDomainsToIgnore());
    }
}
