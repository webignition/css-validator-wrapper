<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\WebResourceInterfaces\WebPageInterface;

class SourcePreparer
{
    private $resourceStorage;

    public function __construct()
    {
        $this->resourceStorage = new ResourceStorage();
    }

    /**
     * @param WebPageInterface $webPage
     * @param SourceMap $sourceMap
     * @param array $stylesheetUrls
     *
     * @return ResourceStorage
     *
     * @throws UnknownSourceException
     */
    public function store(WebPageInterface $webPage, SourceMap $sourceMap, array $stylesheetUrls): ResourceStorage
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

        return $this->resourceStorage;
    }
}
