<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
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

        $resourceStorage = new ResourceStorage();

        $webPageResourcePath = $resourceStorage->store((string) $webPage->getContent(), 'html');

        $cssResourceTemporaryPaths = [];

        foreach ($stylesheetUrls as $stylesheetUrl) {
            $localPath = $sourceMap->getLocalPath($stylesheetUrl);

            $cssResourceTemporaryPaths[] = $resourceStorage->duplicate($localPath, 'css');
        }

        $resourceStorage->deleteAll();

        return $preparedWebPage;
    }
}
