<?php

namespace webignition\CssValidatorWrapper;

use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\Uri\Normalizer;
use webignition\Uri\Uri;
use webignition\WebResource\WebPage\ContentEncodingValidator;
use webignition\WebResource\WebPage\WebPage;

class SourceInspector
{
    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public function findStylesheetUrls(WebPage $webPage): array
    {
        return $this->createAbsoluteUrlCollection(
            new Uri((string) $webPage->getBaseUrl()),
            $this->findLinkElementHrefValues($webPage)
        );
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public function findIeConditionalCommentStylesheetUrls(WebPage $webPage): array
    {
        return $this->createAbsoluteUrlCollection(
            new Uri((string) $webPage->getBaseUrl()),
            $this->findIeConditionalCommentStylesheetHrefValues($webPage)
        );
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    public function findStylesheetReferences(WebPage $webPage): array
    {
        $encoding = $webPage->getCharacterEncoding();
        $encoding = $encoding ?? 'utf-8';

        $references = [];
        $hrefValues = $this->findLinkElementHrefValues($webPage);

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

        $webPageContent = (string) $webPage->getContent();

        foreach ($modifiedHrefAttributes as $hrefValue) {
            $webPageFragment = $webPageContent;

            while (null !== ($reference = $this->findStylesheetUrlReference($webPageFragment, $hrefValue, $encoding))) {
                $reference = (string) $reference;
                $references[] = $reference;

                $referencePosition = mb_strpos($webPageFragment, $reference, 0, $encoding);
                $referenceLength = mb_strlen($reference);

                $webPageFragment = mb_substr($webPageFragment, $referencePosition + $referenceLength, null, $encoding);
            }
        }

        return array_values(array_unique($references, SORT_STRING));
    }

    /**
     * @param WebPage $webPage
     * @param string $reference
     *
     * @return string[]
     */
    public function findStylesheetReferenceFragments(WebPage $webPage, string $reference): array
    {
        if (preg_match('/rel\s*=\s*("|\')stylesheet/', $reference)) {
            return [
                $reference,
            ];
        }

        $encoding = $webPage->getCharacterEncoding();
        $encoding = $encoding ?? 'utf-8';
        $fragments = [];

        $content = (string) $webPage->getContent();

        $referenceStartPosition = mb_strpos($content, $reference, 0, $encoding);

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

    private function findStylesheetUrlReference(string $content, string $hrefValue, string $encoding): ?string
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
            return $this->findStylesheetUrlReference(
                mb_substr($content, $hrefValueEndPosition, null, $encoding),
                $hrefValue,
                $encoding
            );
        }

        return $hrefLinkPrefix . $hrefValue;
    }

    private function findLinkElementHrefValues(WebPage $webPage): array
    {
        $hrefAttributes = [];
        $selector = 'link[rel=stylesheet][href]';
        $webPageInspector = $webPage->getInspector();

        /* @var \DOMElement[] $stylesheetLinkElements */
        $stylesheetLinkElements = $webPageInspector->querySelectorAll($selector);
        if (empty($stylesheetLinkElements)) {
            return $hrefAttributes;
        }

        foreach ($stylesheetLinkElements as $stylesheetLinkElement) {
            $hrefAttributes[] = $stylesheetLinkElement->getAttribute('href');
        }

        return $hrefAttributes;
    }

    private function findIeConditionalCommentStylesheetHrefValues(WebPage $webPage): array
    {
        $inspector = $webPage->getInspector();
        $ieConditionalCommentData = $inspector->extractIeConditionalCommentData();

        $hrefValues = [];

        foreach ($ieConditionalCommentData as $commentData) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $webPageFromComment = WebPage::createFromContent($commentData);

            if ($webPageFromComment instanceof WebPage) {
                $contentEncodingValidator = new ContentEncodingValidator();
                if (!$contentEncodingValidator->isValid($webPageFromComment)) {
                    $webPageFromComment = $contentEncodingValidator->convertToUtf8($webPageFromComment);
                }

                $hrefValues = array_merge($hrefValues, $this->findLinkElementHrefValues($webPageFromComment));
            }
        }

        return $hrefValues;
    }

    /**
     * @param Uri $baseUri
     * @param string[] $hrefValues
     *
     * @return string[]
     */
    private function createAbsoluteUrlCollection(Uri $baseUri, array $hrefValues): array
    {
        $urls = [];

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
}
