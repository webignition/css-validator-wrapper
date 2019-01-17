<?php

namespace webignition\CssValidatorWrapper\Tests\Factory;

class WebPageFixtureModifier
{
    public static function addLineReturnsToLinkElements(string $webPageContent, array $linkElements): string
    {
        $replacements = [];

        foreach ($linkElements as $linkElement) {
            $replacements[] = self::addLineReturnsToLinkElement($linkElement);
        }

        return str_replace($linkElements, $replacements, $webPageContent);
    }

    public static function repeatContent(string $webPageContent, string $contentToRepeat, int $times = 2): string
    {
        $replacement = rtrim(str_repeat($contentToRepeat . "\n", $times), "\n");

        return str_replace($contentToRepeat, $replacement, $webPageContent);
    }

    private static function addLineReturnsToLinkElement(string $linkElement): string
    {
        $parts = explode(' ', $linkElement);
        $partCount = count($parts);

        $updatedLinkElement = '';

        foreach ($parts as $partIndex => $part) {
            $updatedLinkElement .= $part;

            if ($partIndex < $partCount - 1) {
                $updatedLinkElement .= "\n            ";
            }
        }

        return $updatedLinkElement;
    }
}
