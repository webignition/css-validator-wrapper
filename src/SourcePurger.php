<?php

namespace webignition\CssValidatorWrapper;

use webignition\UrlSourceMap\SourceMap;

class SourcePurger
{
    public function purgeLocalResources(SourceMap $sources)
    {
        $filePathPattern = '/^file:/';

        foreach ($sources as $source) {
            if (preg_match($filePathPattern, $source->getMappedUri())) {
                $path = preg_replace('/^file:/', '', $source->getMappedUri());

                @unlink($path);
            }
        }
    }
}
