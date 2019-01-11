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
            $referenceAbsoluteUrl = AbsoluteUrlDeriver::derive(new Uri($baseUrl), new Uri($hrefUrl));
            $localPath = $sourceMap->getLocalPath($referenceAbsoluteUrl);
            $referenceWithoutHrefValue = $this->stripHrefValueFromReference($reference, $hrefUrl);

            $referenceReplacement = $referenceWithoutHrefValue . 'file:' . $localPath;

            $webPageContent = str_replace($reference, $referenceReplacement, $webPageContent);
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
        $hrefAndValue = StringUtils::findClosestAdjoiningStringStartingWith($reference, 'href', $encoding);

        return ltrim(
            preg_replace('/^href/', '', $hrefAndValue),
            ' "\'='
        );
    }
}
