<?php

namespace webignition\Tests\CssValidatorWrapper\Factory;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\WebResource\WebResource;

class ResponseFactory
{
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_CSS = 'text/css';

//    /**
//     * @param string $fixtureName
//     * @param string $contentType
//     *
//     * @return ResponseInterface
//     */
//    public static function createFromFixture($fixtureName, $contentType = self::CONTENT_TYPE_HTML)
//    {
//        return self::create(FixtureLoader::load($fixtureName), $contentType);
//    }

    /**
     * @param string $content
     * @param int $status
     *
     * @return ResponseInterface
     */
    public static function createHtmlResponse($content = '', $status = 200)
    {
        return self::create(self::CONTENT_TYPE_HTML, $content, $status);
    }

    /**
     * @param int $status
     * @param string $content
     *
     * @return ResponseInterface
     */
    public static function createCssResponse($content = '', $status = 200)
    {
        return self::create(self::CONTENT_TYPE_CSS, $content, $status);
    }

    /**
     * @param string $contentType
     * @param string $content
     * @param int $status
     *
     * @return ResponseInterface
     */
    public static function create(
        $contentType,
        $content = '',
        $status = 200
    ) {
        return new Response($status, [WebResource::HEADER_CONTENT_TYPE => $contentType], $content);
    }
}
