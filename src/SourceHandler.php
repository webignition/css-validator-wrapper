<?php

namespace webignition\CssValidatorWrapper;

use webignition\UrlSourceMap\SourceMap;
use webignition\WebResource\WebPage\ContentEncodingValidator;
use webignition\WebResource\WebPage\WebPage;

class SourceHandler
{
    private $webPage;
    private $sourceMap;

    public function __construct(WebPage $webPage, SourceMap $sourceMap)
    {
        $contentEncodingValidator = new ContentEncodingValidator();
        if (!$contentEncodingValidator->isValid($webPage)) {
            $webPage = $contentEncodingValidator->convertToUtf8($webPage);
        }

        $this->webPage = $webPage;
        $this->sourceMap = $sourceMap;
    }

    public function getWebPage(): WebPage
    {
        return $this->webPage;
    }

    public function getSourceMap(): SourceMap
    {
        return $this->sourceMap;
    }
}
