<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\UrlSourceMap\SourceMap;
use webignition\WebResourceInterfaces\WebPageInterface;

class SourceStorage
{
    private $resourceStorage;

    public function __construct(ResourceStorage $resourceStorage)
    {
        $this->resourceStorage = $resourceStorage;
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
                if (!$sourceMap->getByUri($stylesheetUrl)) {
                    throw new UnknownSourceException($stylesheetUrl);
                }
            }
        }

        foreach ($stylesheetUrls as $stylesheetUrl) {
            $source = $sourceMap->getByUri($stylesheetUrl);

            if ($source->isAvailable()) {
                $localPath = preg_replace('/^file:/', '', $source->getMappedUri());

                $this->resourceStorage->duplicate($stylesheetUrl, $localPath, 'css');
            }
        }
    }

    public function deleteAll()
    {
        $this->resourceStorage->deleteAll();
    }
}
