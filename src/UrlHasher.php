<?php

namespace webignition\CssValidatorWrapper;

class UrlHasher
{
    public static function create(string $url): string
    {
        return md5($url);
    }
}
