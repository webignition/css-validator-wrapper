<?php

namespace webignition\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\UriInterface;
use webignition\CssValidatorOutput\Model\ErrorMessage;
use webignition\CssValidatorOutput\Model\ExceptionOutput;
use webignition\CssValidatorOutput\Model\OutputInterface;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Parser\InvalidValidatorOutputException;
use webignition\CssValidatorOutput\Parser\OutputParser;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebPageInspector\UnparseableContentTypeException;
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
     * @return OutputInterface
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidValidatorOutputException
     * @throws UnparseableContentTypeException
     */
    public function validate(Configuration $configuration): OutputInterface
    {
        $sourceUrl = $configuration->getUrlToValidate();
        $localProxyResource = new LocalProxyResource($configuration, $this->httpClient);

        try {
            $localProxyResource->prepare();
        } catch (InvalidContentTypeExceptionInterface $invalidContentTypeException) {
            return new ExceptionOutput(
                ExceptionOutput::TYPE_UNKNOWN_CONTENT_TYPE,
                $invalidContentTypeException->getContentType()->getTypeSubtypeString()
            );
        } catch (HttpException $httpException) {
            return new ExceptionOutput(
                ExceptionOutput::TYPE_HTTP,
                (string) $httpException->getCode()
            );
        } catch (TransportException $transportException) {
            if ($transportException->isCurlException()) {
                return new ExceptionOutput(
                    ExceptionOutput::TYPE_CURL,
                    (string) $transportException->getTransportErrorCode()
                );
            }
        }

        $rawValidatorOutput = shell_exec($configuration->createExecutableCommand());

        $validatorOutput = $this->replaceLocalFilePathsWithOriginalFilePaths(
            $localProxyResource,
            $rawValidatorOutput,
            $sourceUrl
        );

        $cssValidatorOutputParser = new OutputParser();

        /* @var ValidationOutput $output */
        $output = $cssValidatorOutputParser->parse(
            $validatorOutput,
            $configuration->getOutputParserConfiguration()
        );

        if (!$output->isValidationOutput()) {
            $localProxyResource->reset();

            return $output;
        }

        $messageList = $output->getMessages();

        $httpExceptions = $localProxyResource->getHttpExceptions();
        $transportExceptions = $localProxyResource->getTransportExceptions();
        $invalidResponseContentTypeExceptions = $localProxyResource->getInvalidResponseContentTypeExceptions();

        if (!empty($httpExceptions)) {
            foreach ($httpExceptions as $httpException) {
                $messageList->addMessage($this->createCssValidatorOutputError(
                    'http-error:' . $httpException->getCode(),
                    $httpException->getRequest()->getUri()
                ));
            }
        }

        if (!empty($transportExceptions)) {
            foreach ($transportExceptions as $transportException) {
                if ($transportException->isCurlException()) {
                    $messageList->addMessage($this->createCssValidatorOutputError(
                        'curl-error:' . $transportException->getTransportErrorCode(),
                        $transportException->getRequest()->getUri()
                    ));
                }
            }
        }

        if (!empty($invalidResponseContentTypeExceptions)) {
            foreach ($invalidResponseContentTypeExceptions as $invalidResponseContentTypeException) {
                $contentType = $invalidResponseContentTypeException->getContentType();

                $messageList->addMessage($this->createCssValidatorOutputError(
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

    private function createCssValidatorOutputError(string $message, UriInterface $uri): ErrorMessage
    {
        return new ErrorMessage(
            $message,
            0,
            '',
            (string)$uri
        );
    }
}
