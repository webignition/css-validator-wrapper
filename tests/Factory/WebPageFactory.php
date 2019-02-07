<?php

namespace webignition\CssValidatorWrapper\Tests\Factory;

use Psr\Http\Message\UriInterface;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebPage\ContentEncodingValidator;
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

        if ($webPage instanceof WebPage) {
            $contentEncodingValidator = new ContentEncodingValidator();
            if (!$contentEncodingValidator->isValid($webPage)) {
                $webPage = $contentEncodingValidator->convertToUtf8($webPage);
            }
        }

        return $webPage;
    }
}
