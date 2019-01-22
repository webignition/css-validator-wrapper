<?php

namespace webignition\CssValidatorWrapper\Tests\Factory;

class WebPageFixtureFactory
{
    public static function createMarkupContainingFragment(string $fragment, ?string $charset = null)
    {
        $charsetFragment = null === $charset
            ? ''
            : '<meta charset="' . $charset . '">';

        $content = sprintf(
            '<!doctype html><html lang="en"><head>%s%s</head></html>',
            $charsetFragment,
            $fragment
        );

        if ($charset) {
            $content = mb_convert_encoding($content, $charset, 'utf-8');
        }

        return $content;
    }
}
