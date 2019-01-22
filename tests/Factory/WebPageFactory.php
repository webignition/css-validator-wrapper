<?php

namespace webignition\CssValidatorWrapper\Tests\Factory;

use Psr\Http\Message\UriInterface;
use webignition\CssValidatorWrapper\SourceHandler;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebPage\WebPage;

class WebPageFactory
{
    public static function create(
        string $content,
        UriInterface $uri,
        ?InternetMediaTypeInterface $contentType = null
    ): WebPage {
        /* @var WebPage $webPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $webPage = WebPage::createFromContent($content, $contentType);
        $webPage = $webPage->setUri($uri);

        $sourceHandler = new SourceHandler($webPage, new SourceMap());

        return $sourceHandler->getWebPage();
    }
}
