<?php

namespace webignition\CssValidatorWrapper;

class ResourceStorage
{
    private $paths = [];

    public function store(string $content, string $type): string
    {
        return $this->persist($content, $type, function (string $path, string $content) {
            file_put_contents($path, $content);
        });
    }

    public function duplicate(string $localPath, string $type): string
    {
        return $this->persist($localPath, $type, function (string $path, string $localPath) {
            copy($localPath, $path);
        });
    }

    private function persist(string $hashInput, string $type, callable $persister): string
    {
        $path = $this->createPath($hashInput, $type);

        $persister($path, $hashInput);

        $this->paths[] = $path;

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
