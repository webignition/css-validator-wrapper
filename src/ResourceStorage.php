<?php

namespace webignition\CssValidatorWrapper;

class ResourceStorage
{
    private $paths = [];

    public function store(string $content, string $type): string
    {
        $path = sprintf(
            '%s/%s.%s',
            sys_get_temp_dir(),
            md5($content . microtime(true)),
            $type
        );

        file_put_contents($path, $content);

        $this->paths[] = $path;

        return $path;
    }

    public function deleteAll()
    {
        foreach ($this->paths as $path) {
            @unlink($path);
        }
    }
}
