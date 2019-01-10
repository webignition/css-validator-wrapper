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
     * @return ResourceStorage
     *
     * @throws UnknownSourceException
     */
    public function prepare(WebPage $webPage, SourceMap $sourceMap): ResourceStorage
    {
        $stylesheetUrls = SourceInspector::findStylesheetUrls($webPage);

        if (count($stylesheetUrls)) {
            foreach ($stylesheetUrls as $stylesheetUrl) {
                if (!$sourceMap->getLocalPath($stylesheetUrl)) {
                    throw new UnknownSourceException($stylesheetUrl);
                }
            }
        }

        $resourceStorage = new ResourceStorage();

        $resourceStorage->store(
            (string) $webPage->getUri(),
            (string) $webPage->getContent(),
            'html'
        );

        $cssResourceTemporaryPaths = [];

        foreach ($stylesheetUrls as $stylesheetUrl) {
            $localPath = $sourceMap->getLocalPath($stylesheetUrl);
            $cssResourceTemporaryPaths[] = $resourceStorage->duplicate($stylesheetUrl, $localPath, 'css');
        }

        return $resourceStorage;
    }
}
