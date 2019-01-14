<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\WebResource\WebPage\WebPage;

class SourcePreparer
{
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

    /**
     * @return ResourceStorage
     *
     * @throws UnknownSourceException
     */
    public function prepare(): ResourceStorage
    {
        $stylesheetUrls = $this->sourceInspector->findStylesheetUrls();

        if (count($stylesheetUrls)) {
            foreach ($stylesheetUrls as $stylesheetUrl) {
                if (!$this->sourceMap->getLocalPath($stylesheetUrl)) {
                    throw new UnknownSourceException($stylesheetUrl);
                }
            }
        }

        $resourceStorage = new ResourceStorage();

        $resourceStorage->store(
            (string) $this->webPage->getUri(),
            (string) $this->webPage->getContent(),
            'html'
        );

        $cssResourceTemporaryPaths = [];

        foreach ($stylesheetUrls as $stylesheetUrl) {
            $localPath = $this->sourceMap->getLocalPath($stylesheetUrl);
            $cssResourceTemporaryPaths[] = $resourceStorage->duplicate($stylesheetUrl, $localPath, 'css');
        }

        return $resourceStorage;
    }
}
