<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;
use webignition\ResourceStorage\ResourceStorage;
use webignition\UrlSourceMap\SourceMap;
use webignition\WebResourceInterfaces\WebPageInterface;

class SourceStorage
{
    private $resourceStorage;

    public function __construct()
    {
        $this->resourceStorage = new ResourceStorage();
    }

    /**
     * @param WebPageInterface $webPage
     * @param SourceMap $remoteSources
     * @param SourceMap $localSources
     * @param array $stylesheetUrls
     *
     * @return SourceMap
     *
     * @throws UnknownSourceException
     */
    public function store(
        WebPageInterface $webPage,
        SourceMap $remoteSources,
        SourceMap $localSources,
        array $stylesheetUrls
    ): SourceMap {
        $this->resourceStorage->store($localSources, (string) $webPage->getUri(), $webPage->getContent(), 'html');

        if (count($stylesheetUrls)) {
            foreach ($stylesheetUrls as $stylesheetUrl) {
                if (!$remoteSources->getByUri($stylesheetUrl)) {
                    throw new UnknownSourceException($stylesheetUrl);
                }
            }
        }

        foreach ($stylesheetUrls as $stylesheetUrl) {
            $source = $remoteSources->getByUri($stylesheetUrl);

            if ($source && is_string($source->getMappedUri()) && $source->isAvailable()) {
                $localPath = preg_replace('/^file:/', '', $source->getMappedUri());

                $this->resourceStorage->duplicate($localSources, $stylesheetUrl, $localPath, 'css');
            }
        }

        return $localSources;
    }
}
