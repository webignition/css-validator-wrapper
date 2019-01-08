<?php

namespace webignition\CssValidatorWrapper;

class SourceMap
{
    private $mappings = [];

    public function __construct(array $mappings = [])
    {
        foreach ($mappings as $sourcePath => $localPath) {
            $this->addMapping($sourcePath, $localPath);
        }
    }

    public function addMapping(string $sourcePath, string $localPath)
    {
        $this->mappings[$sourcePath] = $localPath;
    }

    public function getLocalPath(string $sourcePath): ?string
    {
        return $this->mappings[$sourcePath] ?? null;
    }
}
