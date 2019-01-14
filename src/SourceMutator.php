<?php

namespace webignition\CssValidatorWrapper;

use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\Uri\Uri;
use webignition\WebResource\WebPage\WebPage;

class SourceMutator
{
    public function replaceStylesheetUrls(WebPage $webPage, SourceMap $sourceMap, array $stylesheetReferences): WebPage
    {
        if (empty($stylesheetReferences)) {
            return $webPage;
        }

        $webPageContent = $webPage->getContent();
        $encoding = $webPage->getCharacterSet();
        $baseUrl = $webPage->getBaseUrl();

        foreach ($stylesheetReferences as $reference) {
            $hrefUrl = $this->getReferenceHrefValue($reference, $encoding);

            if ($hrefUrl) {
                $referenceAbsoluteUrl = AbsoluteUrlDeriver::derive(new Uri($baseUrl), new Uri($hrefUrl));
                $localPath = $sourceMap->getLocalPath($referenceAbsoluteUrl);
                $referenceWithoutHrefValue = $this->stripHrefValueFromReference($reference, $hrefUrl);

                $referenceReplacement = $referenceWithoutHrefValue . 'file:' . $localPath;

                $webPageContent = str_replace($reference, $referenceReplacement, $webPageContent);
            } else {
                $referenceFragments = SourceInspector::findStylesheetReferenceFragments($webPage, $reference);

                foreach ($referenceFragments as $fragment) {
                    $fragmentReplacement = preg_replace(
                        '/href\s*=\s*("|\')\s*("|\')/',
                        'href="file:/"',
                        $fragment
                    );

                    $webPageContent = str_replace($fragment, $fragmentReplacement, $webPageContent);
                }
            }
        }

        /* @var WebPage $mutatedWebPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $mutatedWebPage = $webPage->setContent($webPageContent);

        return $mutatedWebPage;
    }

    private function stripHrefValueFromReference(string $reference, string $hrefUrl)
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
}
