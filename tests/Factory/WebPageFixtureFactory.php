<?php

namespace webignition\CssValidatorWrapper\Tests\Factory;

class WebPageFixtureFactory
{
    public static function createMarkupContainingFragment(
        string $fragment,
        ?string $charset = null,
        ?string $encoding = null
    ): string {
        $charsetFragment = null === $charset
            ? ''
            : '<meta charset="' . $charset . '">';

        $content = sprintf(
            '<!doctype html><html lang="en"><head>%s%s</head></html>',
            $charsetFragment,
            $fragment
        );

        if (empty($encoding) && !empty($charset)) {
            $encoding = $charset;
        }

        if ($encoding) {
            $content = mb_convert_encoding($content, $encoding, 'utf-8');
        }

        return $content;
    }
}
