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

                $localUri = $source->isAvailable()
                    ? $source->getLocalUri()
                    : self::EMPTY_STYLESHEET_HREF_URL;

                $referenceWithoutHrefValue = $this->stripHrefValueFromReference($reference, $hrefUrl);
                $referenceReplacement = $referenceWithoutHrefValue . $localUri;

                $webPageContent = str_replace($reference, $referenceReplacement, $webPageContent);
            } else {
                $referenceFragments = $this->sourceInspector->findStylesheetReferenceFragments($reference);

                foreach ($referenceFragments as $fragment) {
                    $fragmentReplacement = preg_replace(
                        '/href\s*=\s*("|\')\s*("|\')/',
                        'href="' . self::EMPTY_STYLESHEET_HREF_URL . '"',
                        $fragment
                    );

                    $webPageContent = str_replace($fragment, $fragmentReplacement, $webPageContent);
                }
            }
        }

        /* @var WebPage $mutatedWebPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $mutatedWebPage = $this->webPage->setContent($webPageContent);

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
