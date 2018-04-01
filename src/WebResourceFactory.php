<?php

namespace webignition\CssValidatorWrapper;

use Psr\Http\Message\UriInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResourceInterfaces\WebPageInterface;
use webignition\WebResourceInterfaces\WebResourceInterface;

class WebResourceFactory
{
    /**
     * @param string $content
     * @param UriInterface $uri
     *
     * @return WebPageInterface|WebResourceInterface
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     */
    public static function create($content, UriInterface $uri)
    {
        $httpResponse = HttpResponseFactory::create($content);

        $contentTypeHeader = $httpResponse->getHeader('Content-Type');
        $contentTypeString = $contentTypeHeader[0];

        $contentType = self::createMediaTypeFromContentTypeString($contentTypeString);

        if (WebPage::models($contentType)) {
            return new WebPage($httpResponse, $uri);
        }

        return new WebResource($httpResponse, $uri);
    }

    /**
     * @param string $contentTypeString
     *
     * @return InternetMediaTypeInterface
     */
    private static function createMediaTypeFromContentTypeString($contentTypeString)
    {
        $mediaType = new InternetMediaType();
        $mediaTypeParts = explode('/', $contentTypeString);

        $mediaType->setType($mediaTypeParts[0]);
        $mediaType->setSubtype($mediaTypeParts[1]);

        return $mediaType;
    }
}
