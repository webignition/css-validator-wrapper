<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\WebResource\WebPage\WebPage;

class SourcePreparer
{
    private $sourceInspector;

    public function __construct(SourceInspector $sourceInspector)
    {
        $this->sourceInspector = $sourceInspector;
    }

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
        $this->sourceInspector->setWebPage($webPage);

        $stylesheetUrls = $this->sourceInspector->findStylesheetUrls();

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
