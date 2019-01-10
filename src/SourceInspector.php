<?php

namespace webignition\CssValidatorWrapper;

use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\Uri\Normalizer;
use webignition\Uri\Uri;
use webignition\WebResource\WebPage\WebPage;

class SourceInspector
{
    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public static function findStylesheetUrls(WebPage $webPage): array
    {
        $stylesheetUrls = [];
        $selector = 'link[rel=stylesheet][href]';
        $webPageInspector = $webPage->getInspector();

        /* @var \DOMElement[] $stylesheetLinkElements */
        $stylesheetLinkElements = $webPageInspector->querySelectorAll($selector);
        if (empty($stylesheetLinkElements)) {
            return $stylesheetUrls;
        }

        if (empty($stylesheetLinkElements)) {
            return $stylesheetUrls;
        }

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
