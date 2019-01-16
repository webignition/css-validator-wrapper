<?php

namespace webignition\CssValidatorWrapper;

use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\Uri\Uri;
use webignition\WebResource\WebPage\WebPage;

class SourceMutator
{
    const EMPTY_STYLESHEET_HREF_URL = 'file:/null';


    private $webPage;
    private $sourceMap;
    private $sourceInspector;

    public function __construct(WebPage $webPage, SourceMap $sourceMap, SourceInspector $sourceInspector)
    {
        $this->webPage = $webPage;
        $this->sourceMap = $sourceMap;
        $this->sourceInspector = $sourceInspector;
    }

    public function getWebPage(): WebPage
    {
        return $this->webPage;
    }

    public function replaceStylesheetUrls(array $stylesheetReferences): WebPage
    {
        if (empty($stylesheetReferences)) {
            return $this->webPage;
        }

        $webPageContent = $this->webPage->getContent();
        $encoding = $this->webPage->getCharacterSet();
        $baseUrl = $this->webPage->getBaseUrl();

        foreach ($stylesheetReferences as $reference) {
            $hrefUrl = $this->getReferenceHrefValue($reference, $encoding);

            if ($hrefUrl) {
                $referenceAbsoluteUrl = AbsoluteUrlDeriver::derive(new Uri($baseUrl), new Uri($hrefUrl));
                $source = $this->sourceMap->getByUri($referenceAbsoluteUrl);

                if ($source->isAvailable()) {
                    $referenceWithoutHrefValue = $this->stripHrefValueFromReference($reference, $hrefUrl);
                    $referenceReplacement = $referenceWithoutHrefValue . $source->getLocalUri();

                    $webPageContent = str_replace($reference, $referenceReplacement, $webPageContent);
                } else {
                    $referenceReplacement = $this->removeRelStylesheetFromReference($reference);

                    if ($reference !== $referenceReplacement) {
                        $webPageContent = str_replace($reference, $referenceReplacement, $webPageContent);
                    } else {
                        $webPageContent = $this->replaceReferenceByFragment($webPageContent, $reference);
                    }
                }
            } else {
                $webPageContent = $this->replaceReferenceByFragment($webPageContent, $reference);
            }
        }

        /* @var WebPage $mutatedWebPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $mutatedWebPage = $this->webPage->setContent($webPageContent);

        return $mutatedWebPage;
    }

    private function replaceReferenceByFragment(string $webPageContent, string $reference): string
    {
        $referenceFragments = $this->sourceInspector->findStylesheetReferenceFragments($reference);

        foreach ($referenceFragments as $referenceFragment) {
            $webPageContent = $this->replaceReferenceFragment($webPageContent, $referenceFragment);
        }

        return $webPageContent;
    }

    private function replaceReferenceFragment(string $webPageContent, string $referenceFragment): string
    {
        $quoteCharacter = $this->findLeadingRelStylesheetQuote($referenceFragment);
        $quotedReferenceFragment = $referenceFragment . $quoteCharacter;
        $fragmentReplacement = $this->removeRelStylesheetFromReference($quotedReferenceFragment);

        return str_replace(
            $quotedReferenceFragment,
            $fragmentReplacement,
            $webPageContent
        );
    }

    private function stripHrefValueFromReference(string $reference, string $hrefUrl): string
    {
        return preg_replace('/(' . preg_quote($hrefUrl, '/') . ')$/', '', $reference);
    }

    private function getReferenceHrefValue(string $reference, string $encoding): string
    {
        $hrefAndValue = StringUtils::findPreviousAdjoiningStringStartingWith($reference, 'href', $encoding);

        return ltrim(
            preg_replace('/^href/', '', $hrefAndValue),
            ' "\'='
        );
    }

    private function removeRelStylesheetFromReference(string $reference): string
    {
        $pattern = '/\s+rel\s*=\s*("|\')stylesheet("|\')/';

        return preg_replace($pattern, '', $reference);
    }

    private function findLeadingRelStylesheetQuote(string $fragment): string
    {
        $stylesheetWithLeadingQuoteMatches = [];
        preg_match('/rel\s*=\s*("|\')stylesheet/', $fragment, $stylesheetWithLeadingQuoteMatches);

        $stylesheetWithLeadingQuote = $stylesheetWithLeadingQuoteMatches[0];

        $leadingQuote = preg_replace('/^rel\s*=/', '', $stylesheetWithLeadingQuote);
        $leadingQuote = preg_replace('/stylesheet$/', '', $leadingQuote);

        return trim($leadingQuote);
    }
}
