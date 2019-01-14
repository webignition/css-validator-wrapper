<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Exception\UnknownSourceException;

class SourcePreparer
{
    /**
     * @param SourceMap $sourceMap
     * @param ResourceStorage $resourceStorage
     * @param array $stylesheetUrls
     *
     * @throws UnknownSourceException
     */
    public function prepare(
        SourceMap $sourceMap,
        ResourceStorage $resourceStorage,
        array $stylesheetUrls
    ) {
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
            $cssResourceTemporaryPaths[] = $resourceStorage->duplicate($stylesheetUrl, $localPath, 'css');
        }
    }
}
