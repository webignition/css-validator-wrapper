<?php

namespace webignition\CssValidatorWrapper;

use Psr\Http\Message\UriInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResource\WebResourceProperties;
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
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public static function create(string $content, UriInterface $uri): WebResourceInterface
    {
        $contentType = self::deriveContentTypeFromContent($content);

        if (WebPage::models($contentType)) {
            return new WebPage(WebResourceProperties::create([
                WebResourceProperties::ARG_URI => $uri,
                WebResourceProperties::ARG_CONTENT => $content,
                WebResourceProperties::ARG_CONTENT_TYPE => $contentType
            ]));
        }

        return new WebResource(WebResourceProperties::create([
            WebResourceProperties::ARG_URI => $uri,
            WebResourceProperties::ARG_CONTENT => $content,
            WebResourceProperties::ARG_CONTENT_TYPE => $contentType
        ]));
    }

    private static function deriveContentTypeFromContent(string $content): InternetMediaTypeInterface
    {
        $type = 'text';
        $subtype = strip_tags($content) === $content ? 'css' : 'html';

        return new InternetMediaType($type, $subtype);
    }
}
