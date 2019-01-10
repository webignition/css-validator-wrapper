<?php

namespace webignition\CssValidatorWrapper;

class ResourceStorage
{
    private $paths = [];

    public function store(string $url, string $content, string $type): string
    {
        return $this->persist($url, $content, $type, function (string $path, string $content) {
            file_put_contents($path, $content);
        });
    }

    public function duplicate(string $url, string $localPath, string $type): string
    {
        return $this->persist($url, $localPath, $type, function (string $path, string $localPath) {
            copy($localPath, $path);
        });
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    public function getPath(string $url): ?string
    {
        return $this->paths[$url] ?? null;
    }

    private function persist(string $url, string $hashInput, string $type, callable $persister): string
    {
        $path = $this->createPath($hashInput, $type);

        $persister($path, $hashInput);

        $this->paths[$url] = $path;

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

    public function deleteAll()
    {
        foreach ($this->paths as $path) {
            @unlink($path);
        }
    }
}
