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
    public static function findStylesheetUrlReferences(WebPage $webPage): array
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

            $stylesheetUrlReference = self::findStylesheetUrlReference(
                $webPageFragment,
                $hrefValue,
                $encoding
            );

            while (null !== $stylesheetUrlReference) {
                $references[] = $stylesheetUrlReference;

                $referencePosition = mb_strpos($webPageFragment, $stylesheetUrlReference, null, $encoding);
                $referenceLength = mb_strlen($stylesheetUrlReference);

                $webPageFragment = mb_substr($webPageFragment, $referencePosition + $referenceLength, null, $encoding);

                $stylesheetUrlReference = self::findStylesheetUrlReference(
                    $webPageFragment,
                    $hrefValue,
                    $encoding
                );
            }
        }

        return array_values(array_unique($references));
    }

    /**
     * @param string $webPageContent
     * @param string $hrefAttributeValue
     * @param string $encoding
     *
     * @return null
     */
    private static function findStylesheetUrlReference(
        string $webPageContent,
        string $hrefAttributeValue,
        string $encoding
    ) {
        $attributeValueStartPosition = mb_strpos($webPageContent, $hrefAttributeValue, 0, $encoding);

        if (false === $attributeValueStartPosition) {
            return null;
        }

        $hrefAttributeValueLength = mb_strlen($hrefAttributeValue, $encoding);
        $linkIdentifier = '<link';
        $linkIdentifierLength = strlen($linkIdentifier);
        $linkStartPosition = null;

        $attributeValueEndPosition = $attributeValueStartPosition + mb_strlen($hrefAttributeValue, $encoding);

        $webPageFragment = mb_substr(
            $webPageContent,
            0,
            $attributeValueEndPosition - $hrefAttributeValueLength,
            $encoding
        );

        $linkStartPositionOffset = 0;

        $mutableWebPageFragment = $webPageFragment;

        while (mb_strlen($mutableWebPageFragment) > 0 && null === $linkStartPosition) {
            $possibleLinkIdentifier = mb_substr($mutableWebPageFragment, ($linkIdentifierLength * -1), null, $encoding);

            if ($possibleLinkIdentifier === $linkIdentifier) {
                $linkStartPosition = $attributeValueStartPosition - $linkStartPositionOffset;
            } else {
                $mutableWebPageFragment = mb_substr(
                    $mutableWebPageFragment,
                    0,
                    mb_strlen($mutableWebPageFragment) - 1,
                    $encoding
                );
                $linkStartPositionOffset++;
            }
        }

        if (null === $linkStartPosition) {
            return self::findStylesheetUrlReference(
                mb_substr($webPageContent, $attributeValueEndPosition, null, $encoding),
                $hrefAttributeValue,
                $encoding
            );
        }

        return
            $linkIdentifier .
            mb_substr($webPageFragment, $linkStartPosition, $attributeValueEndPosition, $encoding) .
            $hrefAttributeValue;
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
