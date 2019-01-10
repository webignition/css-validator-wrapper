<?php

namespace webignition\CssValidatorWrapper;

use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\Uri\Normalizer;
use webignition\Uri\Uri;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\WebPage\WebPage;

class SourcePreparer
{
    /**
     * @param WebPage $webPage
     * @param SourceMap $sourceMap
     *
     * @return WebPage
     *
     * @throws UnknownSourceException
     */
    public function prepare(WebPage $webPage, SourceMap $sourceMap): WebPage
    {
        $preparedWebPage = clone $webPage;

        $stylesheetUrls = SourceInspector::findStylesheetUrls($webPage);

        if (count($stylesheetUrls)) {
            foreach ($stylesheetUrls as $stylesheetUrl) {
                if (!$sourceMap->getLocalPath($stylesheetUrl)) {
                    throw new UnknownSourceException($stylesheetUrl);
                }
            }
        }

        return $preparedWebPage;
    }
}
