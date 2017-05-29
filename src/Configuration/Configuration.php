<?php

namespace webignition\CssValidatorWrapper\Configuration;

use GuzzleHttp\Client as HttpClient;
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
     * @param string $content
     *
     * @return Configuration
     */
    public function setContentToValidate($content)
    {
        $this->contentToValidate = $content;

        return $this;
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
            $this->webResourceService = new WebResourceService();
            $this->webResourceService->getConfiguration()->setContentTypeWebResourceMap(array(
                'text/html' => WebPage::class,
                'text/css' => WebResource::class
            ));
            $this->webResourceService->getConfiguration()->disableAllowUnknownResourceTypes();
        }

        return $this->webResourceService;
    }

    /**
     * @param HttpClient $httpClient
     *
     * @return self
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
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
     *
     * @return Configuration
     */
    public function setJavaExecutablePath($javaExecutablePath)
    {
        $this->javaExecutablePath = $javaExecutablePath;
        return $this;
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
     *
     * @return Configuration
     */
    public function setCssValidatorJarPath($cssValidatorJarPath)
    {
        $this->cssValidatorJarPath = $cssValidatorJarPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getCssValidatorJarPath()
    {
        return is_null($this->cssValidatorJarPath)
            ? self::DEFAULT_CSS_VALIDATOR_JAR_PATH
            : $this->cssValidatorJarPath;
    }

    /**
     * @return string
     */
    public function getOutputFormat()
    {
        return self::DEFAULT_OUTPUT_FORMAT;
    }

    /**
     * @param string $vendorExtensionSeverityLevel
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function setVendorExtensionSeverityLevel($vendorExtensionSeverityLevel)
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

        return $this;
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
     *
     * @return self
     */
    public function setUrlToValidate($url)
    {
        $this->urlToValidate = trim($url);

        return $this;
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
     *
     * @return Configuration
     */
    public function setFlag($flag)
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
        return $this;
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
     *
     * @return Configuration
     */
    public function setDomainsToIgnore($domainsToIgnore)
    {
        $this->domainsToIgnore = $domainsToIgnore;

        return $this;
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
