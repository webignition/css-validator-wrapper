<?php

namespace webignition\CssValidatorWrapper;

use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\Uri\Normalizer;
use webignition\Uri\Uri;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\WebPage\WebPage;

class SourceInspector
{
    private $webPageInspector;

    public function __construct(WebPageInspector $webPageInspector)
    {
        $this->webPageInspector = $webPageInspector;
    }

    /**
     * @return string[]
     */
    public function findStylesheetUrls(): array
    {
        $stylesheetUrls = [];

        $selector = 'link[rel=stylesheet][href]';

        /* @var \DOMElement[] $stylesheetLinkElements */
        $stylesheetLinkElements = $this->webPageInspector->querySelectorAll($selector);
        if (empty($stylesheetLinkElements)) {
            return $stylesheetUrls;
        }

        if (empty($stylesheetLinkElements)) {
            return $stylesheetUrls;
        }

        /* @var WebPage $webPage */
        $webPage = $this->webPageInspector->getWebPage();
        $baseUri = new Uri($webPage->getBaseUrl());

        foreach ($stylesheetLinkElements as $stylesheetLinkElement) {
            $hrefAttributeValue = trim($stylesheetLinkElement->getAttribute('href'));

            if (!empty($hrefAttributeValue)) {
                $uri = AbsoluteUrlDeriver::derive($baseUri, new Uri($hrefAttributeValue));
                $uri = Normalizer::normalize($uri);
                $uri = Normalizer::normalize($uri, Normalizer::REMOVE_FRAGMENT);

                $stylesheetUrls[] = (string) $uri;
            }
        }

        return array_unique($stylesheetUrls);
    }
}
