<?php

namespace webignition\CssValidatorWrapper;

use webignition\UrlSourceMap\Source;
use webignition\UrlSourceMap\SourceMap;

class ResourceStorage
{
    public function store(SourceMap $localSources, string $uri, string $content, string $type): string
    {
        return $this->persist($localSources, $uri, $content, $type, function (string $path, string $content) {
            file_put_contents($path, $content);
        });
    }

    public function duplicate(SourceMap $localSources, string $uri, string $localUri, string $type): string
    {
        return $this->persist($localSources, $uri, $localUri, $type, function (string $path, string $localPath) {
            copy($localPath, $path);
        });
    }

    private function persist(
        SourceMap $localSources,
        string $uri,
        string $hashInput,
        string $type,
        callable $persister
    ): string {
        $path = $this->createPath($hashInput, $type);
        $localUri = 'file:' . $path;
        $source = new Source($uri, $localUri);

        $persister($path, $hashInput);

        $localSources[$uri] = $source;

        return $path;
    }

    private function createPath(string $hashInput, string $type): string
    {
        return sprintf(
            '%s/%s.%s',
            sys_get_temp_dir(),
            md5($hashInput . microtime(true)),
            $type
        );
    }
}
