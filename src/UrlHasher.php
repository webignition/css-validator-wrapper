<?php

namespace webignition\CssValidatorWrapper;

class UrlHasher
{
    /**
     * @param string $url
     *
     * @return string
     */
    public static function create($url)
    {
        return md5($url);
    }
}
