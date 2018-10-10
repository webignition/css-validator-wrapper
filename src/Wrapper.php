<?php

namespace webignition\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\UriInterface;
use QueryPath\Exception as QueryPathException;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\CssValidatorOutput\Message\Error as CssValidatorOutputError;
use webignition\CssValidatorOutput\Parser\Parser as CssValidatorOutputParser;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\ExceptionOutput\ExceptionOutput;
use webignition\CssValidatorOutput\ExceptionOutput\Type\Type as ExceptionOutputType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResourceInterfaces\InvalidContentTypeExceptionInterface;

class Wrapper
{
    const INVALID_ARGUMENT_EXCEPTION_CONFIGURATION_NOT_SET = 1;
    const INVALID_ARGUMENT_EXCEPTION_URL_TO_VALIDATE_NOT_SET = 2;

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param Configuration $configuration
     *
     * @return CssValidatorOutput
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidValidatorOutputException
     * @throws QueryPathException
     */
    public function validate(Configuration $configuration): CssValidatorOutput
    {
        $sourceUrl = $configuration->getUrlToValidate();
        $localProxyResource = new LocalProxyResource($configuration, $this->httpClient);

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

        $rawValidatorOutput = shell_exec($configuration->getExecutableCommand());

        $validatorOutput = $this->replaceLocalFilePathsWithOriginalFilePaths(
            $localProxyResource,
            $rawValidatorOutput,
            $sourceUrl
        );

        $cssValidatorOutputParser = new CssValidatorOutputParser();

        $output = $cssValidatorOutputParser->parse(
            $validatorOutput,
            $configuration->getOutputParserConfiguration()
        );

        $httpExceptions = $localProxyResource->getHttpExceptions();
        $transportExceptions = $localProxyResource->getTransportExceptions();
        $invalidResponseContentTypeExceptions = $localProxyResource->getInvalidResponseContentTypeExceptions();

        if (!empty($httpExceptions)) {
            foreach ($httpExceptions as $httpException) {
                $output->addMessage($this->createCssValidatorOutputError(
                    'http-error:' . $httpException->getCode(),
                    $httpException->getRequest()->getUri()
                ));
            }
        }

        if (!empty($transportExceptions)) {
            foreach ($transportExceptions as $transportException) {
                if ($transportException->isCurlException()) {
                    $output->addMessage($this->createCssValidatorOutputError(
                        'curl-error:' . $transportException->getTransportErrorCode(),
                        $transportException->getRequest()->getUri()
                    ));
                }
            }
        }

        if (!empty($invalidResponseContentTypeExceptions)) {
            foreach ($invalidResponseContentTypeExceptions as $invalidResponseContentTypeException) {
                $contentType = $invalidResponseContentTypeException->getContentType();

                $output->addMessage($this->createCssValidatorOutputError(
                    'invalid-content-type:' . $contentType->getTypeSubtypeString(),
                    $invalidResponseContentTypeException->getRequest()->getUri()
                ));
            }
        }

        $localProxyResource->reset();

        return $output;
    }

    private function replaceLocalFilePathsWithOriginalFilePaths(
        LocalProxyResource $localProxyResource,
        string $validatorOutput,
        string $sourceUrl
    ): string {
        $refMatches = [];
        preg_match_all('/ref="file:\/tmp\/[^"]*"/', $validatorOutput, $refMatches);

        $webResourceStorage = $localProxyResource->getWebResourceStorage();

        if (count($refMatches) > 0) {
            $refAttributes = $refMatches[0];

            foreach ($refAttributes as $index => $refAttribute) {
                if ($index === 0) {
                    $originalUrl = $sourceUrl;
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

    private function createCssValidatorOutputError(string $message, UriInterface $uri): CssValidatorOutputError
    {
        return new CssValidatorOutputError(
            $message,
            '',
            (string)$uri,
            0
        );
    }
}
