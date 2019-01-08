<?php

namespace webignition\CssValidatorWrapper;

class SourceMap
{
    private $mappings = [];

    public function addMapping(string $sourcePath, string $localPath)
    {
        $this->mappings[$sourcePath] = $localPath;
    }

    public function getLocalPath(string $sourcePath): ?string
    {
        return $this->mappings[$sourcePath] ?? null;
    }
}
