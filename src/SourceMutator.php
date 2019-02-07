<?php

namespace webignition\CssValidatorWrapper;

use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\Uri\Uri;
use webignition\UrlSourceMap\SourceMap;
use webignition\WebResource\WebPage\WebPage;

class SourceMutator
{
    private $sourceInspector;

    public function __construct()
    {
        $this->sourceInspector = new SourceInspector();
    }

    public function replaceStylesheetUrls(WebPage $webPage, SourceMap $sourceMap, array $stylesheetReferences): WebPage
    {
        if (empty($stylesheetReferences)) {
            return $webPage;
        }

        $mutatedWebPage = clone $webPage;
        $encoding = $webPage->getCharacterEncoding();
        $baseUrl = $webPage->getBaseUrl();

        foreach ($stylesheetReferences as $reference) {
            $hrefUrl = $this->getReferenceHrefValue($reference, $encoding);

            if ($hrefUrl) {
                $referenceAbsoluteUrl = AbsoluteUrlDeriver::derive(new Uri($baseUrl), new Uri($hrefUrl));
                $source = $sourceMap->getByUri($referenceAbsoluteUrl);

                if ($source->isAvailable()) {
                    $referenceWithoutHrefValue = $this->stripHrefValueFromReference($reference, $hrefUrl);
                    $referenceReplacement = $referenceWithoutHrefValue . $source->getMappedUri();

                    $mutatedWebPage = $this->replaceWebPageContent($mutatedWebPage, $reference, $referenceReplacement);
                } else {
                    $referenceReplacement = $this->removeRelStylesheetFromReference($reference);

                    if ($reference !== $referenceReplacement) {
                        $mutatedWebPage = $this->replaceWebPageContent(
                            $mutatedWebPage,
                            $reference,
                            $referenceReplacement
                        );
                    } else {
                        $mutatedWebPage = $this->replaceReferenceByFragment($webPage, $mutatedWebPage, $reference);
                    }
                }
            } else {
                $mutatedWebPage = $this->replaceReferenceByFragment($webPage, $mutatedWebPage, $reference);
            }
        }

        return $mutatedWebPage;
    }

    private function replaceReferenceByFragment(
        WebPage $originalWebPage,
        WebPage $mutatedWebPage,
        string $reference
    ): WebPage {
        $referenceFragments = $this->sourceInspector->findStylesheetReferenceFragments($originalWebPage, $reference);

        foreach ($referenceFragments as $referenceFragment) {
            $mutatedWebPage = $this->replaceReferenceFragment($mutatedWebPage, $referenceFragment);
        }

        return $mutatedWebPage;
    }

    private function replaceReferenceFragment(WebPage $webPage, string $referenceFragment): WebPage
    {
        $quoteCharacter = $this->findLeadingRelStylesheetQuote($referenceFragment);
        $quotedReferenceFragment = $referenceFragment . $quoteCharacter;
        $fragmentReplacement = $this->removeRelStylesheetFromReference($quotedReferenceFragment);

        return $this->replaceWebPageContent($webPage, $quotedReferenceFragment, $fragmentReplacement);
    }

    private function stripHrefValueFromReference(string $reference, string $hrefUrl): string
    {
        return (string) preg_replace('/(' . preg_quote($hrefUrl, '/') . ')$/', '', $reference);
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

        return (string) preg_replace($pattern, '', $reference);
    }

    private function findLeadingRelStylesheetQuote(string $fragment): string
    {
        $stylesheetWithLeadingQuoteMatches = [];
        preg_match('/rel\s*=\s*("|\')stylesheet/', $fragment, $stylesheetWithLeadingQuoteMatches);

        $stylesheetWithLeadingQuote = $stylesheetWithLeadingQuoteMatches[0];

        $leadingQuote = preg_replace('/^rel\s*=/', '', $stylesheetWithLeadingQuote);
        $leadingQuote = $leadingQuote ?? '';

        $leadingQuote = preg_replace('/stylesheet$/', '', $leadingQuote);

        return trim($leadingQuote);
    }

    private function replaceWebPageContent(WebPage $webPage, string $search, string $replace): WebPage
    {
        /* @var WebPage $mutatedWebPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $mutatedWebPage = $webPage->setContent(str_replace($search, $replace, $webPage->getContent()));

        return $mutatedWebPage;
    }
}
