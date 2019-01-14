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
        $urls = [];
        $hrefValues = self::findStylesheetUrlHrefValues($webPage);

        $baseUri = new Uri($webPage->getBaseUrl());

        foreach ($hrefValues as $hrefValue) {
            $hrefValue = trim($hrefValue);

            if (!empty($hrefValue)) {
                $uri = AbsoluteUrlDeriver::derive($baseUri, new Uri($hrefValue));
                $uri = Normalizer::normalize($uri);
                $uri = Normalizer::normalize($uri, Normalizer::REMOVE_FRAGMENT);

                $urls[] = (string) $uri;
            }
        }

        return array_unique($urls);
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public static function findStylesheetReferences(WebPage $webPage): array
    {
        $encoding = $webPage->getCharacterSet();
        $references = [];
        $hrefValues = self::findStylesheetUrlHrefValues($webPage);

        foreach ($hrefValues as $hrefValue) {
            if (mb_substr_count($hrefValue, '&', $encoding)) {
                $hrefValues[] = str_replace('&', '&amp;', $hrefValue);
            }
        }

        $modifiedHrefAttributes = [];

        foreach ($hrefValues as $hrefValue) {
            if ('' === trim($hrefValue)) {
                $modifiedHrefAttributes[] = '"' . $hrefValue . '"';
                $modifiedHrefAttributes[] = "'" . $hrefValue . "'";
            } else {
                $modifiedHrefAttributes[] = $hrefValue;
            }
        }

        $webPageContent = $webPage->getContent();

        foreach ($modifiedHrefAttributes as $hrefValue) {
            $webPageFragment = $webPageContent;

            while (null !== ($reference = self::findStylesheetUrlReference($webPageFragment, $hrefValue, $encoding))) {
                $references[] = $reference;

                $referencePosition = mb_strpos($webPageFragment, $reference, null, $encoding);
                $referenceLength = mb_strlen($reference);

                $webPageFragment = mb_substr($webPageFragment, $referencePosition + $referenceLength, null, $encoding);
            }
        }

        return array_values(array_unique($references));
    }

    /**
     * @param WebPage $webPage
     * @param string $reference
     *
     * @return string[]
     */
    public static function findStylesheetReferenceFragments(WebPage $webPage, string $reference): array
    {
        $encoding = $webPage->getCharacterSet();
        $fragments = [];

        $content = $webPage->getContent();

        $referenceStartPosition = mb_strpos($content, $reference, null, $encoding);

        if (false === $referenceStartPosition) {
            return $fragments;
        }

        $mutableContent = mb_substr($content, $referenceStartPosition, null, $encoding);

        while (false !== ($referenceExistsInContent = mb_substr_count($mutableContent, $reference, $encoding) > 0)) {
            $fragment = StringUtils::findNextAdjoiningStringEndingWith(
                $mutableContent,
                'stylesheet',
                $encoding
            );

            $fragments[] = trim($fragment);

            $fragmentLength = mb_strlen($fragment);
            $mutableContent = mb_substr($mutableContent, $fragmentLength, null, $encoding);
        }

        return $fragments;
    }

    /**
     * @param string $content
     * @param string $hrefValue
     * @param string $encoding
     *
     * @return null
     */
    private static function findStylesheetUrlReference(string $content, string $hrefValue, string $encoding)
    {
        $hrefValueStartPosition = mb_strpos($content, $hrefValue, 0, $encoding);

        if (false === $hrefValueStartPosition) {
            return null;
        }

        $hrefValueEndPosition = $hrefValueStartPosition + mb_strlen($hrefValue, $encoding);

        $hrefLinkPrefix = StringUtils::findPreviousAdjoiningStringStartingWith(
            $content,
            '<link',
            $encoding,
            $hrefValueStartPosition
        );

        if (null === $hrefLinkPrefix) {
            return self::findStylesheetUrlReference(
                mb_substr($content, $hrefValueEndPosition, null, $encoding),
                $hrefValue,
                $encoding
            );
        }

        return $hrefLinkPrefix . $hrefValue;
    }

    private static function findStylesheetUrlHrefValues(WebPage $webPage): array
    {
        $hrefAttributes = [];
        $selector = 'link[rel=stylesheet][href]';
        $webPageInspector = $webPage->getInspector();

        /* @var \DOMElement[] $stylesheetLinkElements */
        $stylesheetLinkElements = $webPageInspector->querySelectorAll($selector);
        if (empty($stylesheetLinkElements)) {
            return $hrefAttributes;
        }

        if (empty($stylesheetLinkElements)) {
            return $hrefAttributes;
        }

        foreach ($stylesheetLinkElements as $stylesheetLinkElement) {
            $hrefAttributes[] = $stylesheetLinkElement->getAttribute('href');
        }

        return $hrefAttributes;
    }
}
