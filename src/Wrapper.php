<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorOutput\Message\Error as CssValidatorOutputError;
use webignition\CssValidatorOutput\Parser\Parser as CssValidatorOutputParser;
use webignition\CssValidatorOutput\Parser\Configuration as CssValidatorOutputParserConfiguration;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\ExceptionOutput\ExceptionOutput;
use webignition\CssValidatorOutput\ExceptionOutput\Type\Type as ExceptionOutputType;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\Exception\Exception as WebResourceException;

class Wrapper
{
    const INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET = 1;
    const INVALID_ARGUMENT_EXCEPTION_URL_TO_VALIDATE_NOT_SET = 2;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var LocalProxyResource
     */
    private $localProxyResource = null;

    /**
     * @param Configuration $configuration
     *
     * @return self
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return boolean
     */
    public function hasConfiguration()
    {
        return !is_null($this->getConfiguration());
    }

    /**
     * @param array $configurationValues
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function createConfiguration($configurationValues)
    {
        if (empty($configurationValues)) {
            throw new \InvalidArgumentException(
                'A non-empty array of configuration values must be passed to create configuration',
                self::INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET
            );
        }

        if (!isset($configurationValues['url-to-validate'])) {
            throw new \InvalidArgumentException(
                'Configuration value "url-to-validate" not set',
                self::INVALID_ARGUMENT_EXCEPTION_URL_TO_VALIDATE_NOT_SET
            );
        }

        $configuration = new Configuration();
        $configuration->setUrlToValidate($configurationValues['url-to-validate']);

        if (isset($configurationValues['java-executable-path'])) {
            $configuration->setJavaExecutablePath($configurationValues['java-executable-path']);
        }

        if (isset($configurationValues['css-validator-jar-path'])) {
            $configuration->setCssValidatorJarPath($configurationValues['css-validator-jar-path']);
        }

        if (isset($configurationValues['vendor-extension-severity-level'])) {
            $configuration->setVendorExtensionSeverityLevel($configurationValues['vendor-extension-severity-level']);
        }

        if (isset($configurationValues['flags']) && is_array($configurationValues['flags'])) {
            foreach ($configurationValues['flags'] as $flag) {
                $configuration->setFlag($flag);
            }
        }

        if (isset($configurationValues['domains-to-ignore']) && is_array($configurationValues['domains-to-ignore'])) {
            $configuration->setDomainsToIgnore($configurationValues['domains-to-ignore']);
        }

        if (isset($configurationValues['http-client']) && $configurationValues['http-client'] instanceof HttpClient) {
            $configuration->setHttpClient($configurationValues['http-client']);
        }

        if (isset($configurationValues['content-to-validate'])) {
            $configuration->setContentToValidate($configurationValues['content-to-validate']);
        }

        $this->setConfiguration($configuration);

        return $this;
    }


    /**
     * @throws \InvalidArgumentException
     *
     * @return \webignition\CssValidatorOutput\CssValidatorOutput
     */
    public function validate()
    {
        if (!$this->hasConfiguration()) {
            throw new \InvalidArgumentException(
                'Unable to validate; configuration not set',
                self::INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET
            );
        }

        try {
            $this->getLocalProxyResource()->prepare();
        } catch (InvalidContentTypeException $invalidContentTypeException) {
            $cssValidatorOutput = new CssValidatorOutput();
            $cssValidatorOutputException = new ExceptionOutput();
            $cssValidatorOutputException->setType(
                new ExceptionOutputType(
                    'invalid-content-type:'
                    . $invalidContentTypeException->getResponseContentType()->getTypeSubtypeString()
                )
            );

            $cssValidatorOutput->setException($cssValidatorOutputException);

            return $cssValidatorOutput;
        } catch (WebResourceException $webResourceException) {
            $cssValidatorOutput = new CssValidatorOutput();
            $cssValidatorOutputException = new ExceptionOutput();
            $cssValidatorOutputException->setType(
                new ExceptionOutputType('http' . $webResourceException->getResponse()->getStatusCode())
            );

            $cssValidatorOutput->setException($cssValidatorOutputException);

            return $cssValidatorOutput;
        } catch (ConnectException $connectException) {
            $curlExceptionFactory = new CurlExceptionFactory();
            if ($curlExceptionFactory->isCurlException($connectException)) {
                $curlException = $curlExceptionFactory->fromConnectException($connectException);

                $cssValidatorOutput = new CssValidatorOutput();
                $cssValidatorOutputException = new ExceptionOutput();
                $cssValidatorOutputException->setType(new ExceptionOutputType('curl' . $curlException->getCurlCode()));

                $cssValidatorOutput->setException($cssValidatorOutputException);

                return $cssValidatorOutput;
            }
        }

        $rawValidatorOutput = shell_exec($this->getLocalProxyResource()->getConfiguration()->getExecutableCommand());

        $validatorOutput = $this->replaceLocalFilePathsWithOriginalFilePaths(
            $rawValidatorOutput
        );

        $cssValidatorOutputParserConfiguration = new CssValidatorOutputParserConfiguration();
        $cssValidatorOutputParserConfiguration->setRawOutput($validatorOutput);

        $this->localProxyResource->clear();

        if ($this->getConfiguration()->hasFlag(Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES)) {
            $cssValidatorOutputParserConfiguration->setIgnoreFalseImageDataUrlMessages(true);
        }

        if ($this->getConfiguration()->hasFlag(Flags::FLAG_IGNORE_WARNINGS)) {
            $cssValidatorOutputParserConfiguration->setIgnoreWarnings(true);
        }

        if ($this->getConfiguration()->getVendorExtensionSeverityLevel()
            === VendorExtensionSeverityLevel::LEVEL_IGNORE) {
            $cssValidatorOutputParserConfiguration->setIgnoreVendorExtensionIssues(true);
        }

        if ($this->getConfiguration()->getVendorExtensionSeverityLevel() === VendorExtensionSeverityLevel::LEVEL_WARN) {
            $cssValidatorOutputParserConfiguration->setReportVendorExtensionIssuesAsWarnings(true);
        }

        if ($this->getConfiguration()->hasDomainsToIgnore()) {
            $cssValidatorOutputParserConfiguration->setRefDomainsToIgnore(
                $this->getConfiguration()->getDomainsToIgnore()
            );
        }

        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setConfiguration($cssValidatorOutputParserConfiguration);

        $output = $cssValidatorOutputParser->getOutput();

        if ($this->getLocalProxyResource()->hasWebResourceExceptions()) {
            foreach ($this->getLocalProxyResource()->getWebResourceExceptions() as $webResourceException) {
                $error = new CssValidatorOutputError();
                $error->setContext('');
                $error->setLineNumber(0);

                if ($webResourceException instanceof InvalidContentTypeException) {
                    $error->setMessage(
                        'invalid-content-type:' . (string)$webResourceException->getResponseContentType()
                    );
                } else {
                    $error->setMessage('http-error:' . $webResourceException->getResponse()->getStatusCode());
                }

                $error->setRef($webResourceException->getRequest()->getUrl());

                $output->addMessage($error);
            }
        }

        if ($this->getLocalProxyResource()->hasCurlExceptions()) {
            foreach ($this->getLocalProxyResource()->getCurlExceptions() as $curlExceptionDetails) {
                $error = new CssValidatorOutputError();
                $error->setContext('');
                $error->setLineNumber(0);
                $error->setMessage('curl-error:' . $curlExceptionDetails['exception']->getCurlCode());
                $error->setRef($curlExceptionDetails['url']);

                $output->addMessage($error);
            }
        }

        return $cssValidatorOutputParser->getOutput();
    }

    /**
     * @return LocalProxyResource
     */
    public function getLocalProxyResource()
    {
        if (is_null($this->localProxyResource)) {
            $this->localProxyResource = new LocalProxyResource($this->getConfiguration());
        }

        return $this->localProxyResource;
    }

    /**
     * @param string $validatorOutput
     *
     * @return string
     */
    private function replaceLocalFilePathsWithOriginalFilePaths($validatorOutput)
    {
        $refMatches = array();
        preg_match_all('/ref="file:\/tmp\/[^"]*"/', $validatorOutput, $refMatches);

        if (count($refMatches) > 0) {
            $refAttributes = $refMatches[0];

            foreach ($refAttributes as $index => $refAttribute) {
                if ($index === 0) {
                    $originalUrl = $this->getLocalProxyResource()->getRootWebResourceUrl();
                } else {
                    $originalUrl = $this->getLocalProxyResource()->getWebResourceUrlFromPath(
                        str_replace(array('ref="file:', '"'), '', $refAttribute)
                    );
                }

                $validatorOutput = str_replace(
                    $refAttribute,
                    'ref="' . htmlspecialchars($originalUrl)  . '"',
                    $validatorOutput
                );
            }
        }

        return $validatorOutput;
    }
}
