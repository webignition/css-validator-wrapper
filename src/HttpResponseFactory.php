<?php

namespace webignition\CssValidatorWrapper;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class HttpResponseFactory
{
    const CSS_CONTENT_TYPE = 'text/css';
    const HTML_CONTENT_TYPE = 'text/html';

    /**
     * @param string $content
     *
     * @return ResponseInterface
     */
    public static function create($content)
    {
        $contentTypeString = self::deriveContentTypeStringFromContent($content);

        return new Response(200, ['Content-Type' => $contentTypeString], $content);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private static function deriveContentTypeStringFromContent($content)
    {
        if (strip_tags($content) !== $content) {
            return self::HTML_CONTENT_TYPE;
        }

        return self::CSS_CONTENT_TYPE;
    }
}
