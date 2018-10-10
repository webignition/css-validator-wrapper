<?php

namespace webignition\CssValidatorWrapper\Tests\Factory;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\WebResource\WebResource;

class ResponseFactory
{
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_CSS = 'text/css';

    public static function createHtmlResponse(string $content = '', int $status = 200): ResponseInterface
    {
        return self::create(self::CONTENT_TYPE_HTML, $content, $status);
    }

    public static function createCssResponse(string $content = '', int $status = 200): ResponseInterface
    {
        return self::create(self::CONTENT_TYPE_CSS, $content, $status);
    }

    public static function create(string $contentType, string $content = '', int $status = 200): ResponseInterface
    {
        return new Response($status, [WebResource::HEADER_CONTENT_TYPE => $contentType], $content);
    }
}
