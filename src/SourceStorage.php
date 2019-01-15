<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\WebResourceInterfaces\WebPageInterface;

class SourceStorage
{
    private $paths;
    private $resourceStorage;

    public function __construct()
    {
        $this->paths = new SourceMap();
        $this->resourceStorage = new ResourceStorage($this->paths);
    }

    public function getPaths(): SourceMap
    {
        return $this->paths;
    }

    /**
     * @param WebPageInterface $webPage
     * @param SourceMap $sourceMap
     * @param array $stylesheetUrls
     *
     * @throws UnknownSourceException
     */
    public function store(WebPageInterface $webPage, SourceMap $sourceMap, array $stylesheetUrls)
    {
        $this->resourceStorage->store((string) $webPage->getUri(), $webPage->getContent(), 'html');

        if (count($stylesheetUrls)) {
            foreach ($stylesheetUrls as $stylesheetUrl) {
                if (!$sourceMap->getLocalPath($stylesheetUrl)) {
                    throw new UnknownSourceException($stylesheetUrl);
                }
            }
        }

        $cssResourceTemporaryPaths = [];

        foreach ($stylesheetUrls as $stylesheetUrl) {
            $localPath = $sourceMap->getLocalPath($stylesheetUrl);
            $cssResourceTemporaryPaths[] = $this->resourceStorage->duplicate($stylesheetUrl, $localPath, 'css');
        }
    }

    public function deleteAll()
    {
        $this->resourceStorage->deleteAll();
    }
}
