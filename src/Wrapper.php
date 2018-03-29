<?php

namespace webignition\CssValidatorWrapper;

use QueryPath\Exception as QueryPathException;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Configuration\Flags;
use webignition\CssValidatorOutput\Message\Error as CssValidatorOutputError;
use webignition\CssValidatorOutput\Parser\Parser as CssValidatorOutputParser;
use webignition\CssValidatorOutput\Parser\Configuration as CssValidatorOutputParserConfiguration;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\ExceptionOutput\ExceptionOutput;
use webignition\CssValidatorOutput\ExceptionOutput\Type\Type as ExceptionOutputType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResourceInterfaces\InvalidContentTypeExceptionInterface;

class Wrapper
{
    const INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET = 1;
    const INVALID_ARGUMENT_EXCEPTION_URL_TO_VALIDATE_NOT_SET = 2;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Configuration
     */
    private $sourceConfiguration;

    /**
     * @var LocalProxyResource
     */
    private $localProxyResource = null;

    public function __construct()
    {
        $this->configuration = new Configuration([]);
        $this->sourceConfiguration = clone $this->configuration;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->sourceConfiguration = clone $this->configuration;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return CssValidatorOutput
     *
     * @throws QueryPathException
     * @throws InternetMediaTypeParseException
     */
    public function validate()
    {
        $localProxyResource = new LocalProxyResource($this->configuration);

        try {
            $localProxyResource->prepare();
        } catch (InvalidContentTypeExceptionInterface $invalidContentTypeException) {
            $cssValidatorOutput = new CssValidatorOutput();
            $cssValidatorOutputException = new ExceptionOutput();
            $cssValidatorOutputException->setType(
                new ExceptionOutputType(
                    'invalid-content-type:'
                    . $invalidContentTypeException->getContentType()->getTypeSubtypeString()
                )
            );

            $cssValidatorOutput->setException($cssValidatorOutputException);

            return $cssValidatorOutput;
        } catch (HttpException $httpException) {
            $cssValidatorOutput = new CssValidatorOutput();
            $cssValidatorOutputException = new ExceptionOutput();
            $cssValidatorOutputException->setType(
                new ExceptionOutputType('http' . $httpException->getCode())
            );

            $cssValidatorOutput->setException($cssValidatorOutputException);

            return $cssValidatorOutput;
        } catch (TransportException $transportException) {
            if ($transportException->isCurlException()) {
                $cssValidatorOutput = new CssValidatorOutput();
                $cssValidatorOutputException = new ExceptionOutput();
                $cssValidatorOutputException->setType(
                    new ExceptionOutputType('curl' . $transportException->getTransportErrorCode())
                );

                $cssValidatorOutput->setException($cssValidatorOutputException);

                return $cssValidatorOutput;
            }
        }

        $rawValidatorOutput = shell_exec($this->configuration->getExecutableCommand());

        $validatorOutput = $this->replaceLocalFilePathsWithOriginalFilePaths(
            $localProxyResource,
            $rawValidatorOutput
        );

        $cssValidatorOutputParserConfiguration = new CssValidatorOutputParserConfiguration();
        $cssValidatorOutputParserConfiguration->setRawOutput($validatorOutput);

        $this->localProxyResource->reset();

        if ($this->configuration->hasFlag(Flags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES)) {
            $cssValidatorOutputParserConfiguration->setIgnoreFalseImageDataUrlMessages(true);
        }

        if ($this->configuration->hasFlag(Flags::FLAG_IGNORE_WARNINGS)) {
            $cssValidatorOutputParserConfiguration->setIgnoreWarnings(true);
        }

        if ($this->configuration->getVendorExtensionSeverityLevel()
            === VendorExtensionSeverityLevel::LEVEL_IGNORE) {
            $cssValidatorOutputParserConfiguration->setIgnoreVendorExtensionIssues(true);
        }

        if ($this->configuration->getVendorExtensionSeverityLevel() === VendorExtensionSeverityLevel::LEVEL_WARN) {
            $cssValidatorOutputParserConfiguration->setReportVendorExtensionIssuesAsWarnings(true);
        }

        if ($this->configuration->hasDomainsToIgnore()) {
            $cssValidatorOutputParserConfiguration->setRefDomainsToIgnore(
                $this->configuration->getDomainsToIgnore()
            );
        }

        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setConfiguration($cssValidatorOutputParserConfiguration);

        $output = $cssValidatorOutputParser->getOutput();

        $httpExceptions = $localProxyResource->getHttpExceptions();
        $transportExceptions = $localProxyResource->getTransportExceptions();

        if (!empty($httpExceptions)) {
            foreach ($httpExceptions as $httpException) {
                $error = new CssValidatorOutputError();
                $error->setContext('');
                $error->setLineNumber(0);

                if ($httpException instanceof InvalidContentTypeException) {
                    $error->setMessage(
                        'invalid-content-type:' . (string)$httpException->getContentType()->getTypeSubtypeString()
                    );
                } else {
                    $error->setMessage('http-error:' . $httpException->getCode());
                }

                $error->setRef((string)$httpException->getRequest()->getUri());

                $output->addMessage($error);
            }
        }

        if (!empty($transportExceptions)) {
            foreach ($transportExceptions as $transportException) {
                if ($transportException->isCurlException()) {
                    $error = new CssValidatorOutputError();
                    $error->setContext('');
                    $error->setLineNumber(0);
                    $error->setMessage('curl-error:' . $transportException->getTransportErrorCode());
                    $error->setRef((string)$transportException->getRequest()->getUri());

                    $output->addMessage($error);
                }
            }
        }

        return $cssValidatorOutputParser->getOutput();
    }

    /**
     * @param LocalProxyResource $localProxyResource
     * @param string $validatorOutput
     *
     * @return string
     */
    private function replaceLocalFilePathsWithOriginalFilePaths(
        LocalProxyResource $localProxyResource,
        $validatorOutput
    ) {
        $refMatches = [];
        preg_match_all('/ref="file:\/tmp\/[^"]*"/', $validatorOutput, $refMatches);

        $webResourceStorage = $localProxyResource->getWebResourceStorage();

        if (count($refMatches) > 0) {
            $refAttributes = $refMatches[0];

            foreach ($refAttributes as $index => $refAttribute) {
                if ($index === 0) {
                    $originalUrl = $this->sourceConfiguration->getUrlToValidate();
                } else {
                    $originalUrl = $webResourceStorage->getUrlFromPath(
                        str_replace(['ref="file:', '"'], '', $refAttribute)
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
