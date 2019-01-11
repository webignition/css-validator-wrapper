<?php

namespace webignition\CssValidatorWrapper;

class StringUtils
{
    public static function findClosestAdjoiningStringStartingWith(
        string $content,
        string $target,
        string $encoding,
        ?int $offset
    ) {
        $fragment = mb_substr(
            $content,
            0,
            $offset,
            $encoding
        );

        $targetStartPosition = null;
        $targetStartPositionOffset = 0;
        $targetLength = mb_strlen($target, $encoding);

        $mutableFragment = $fragment;

        while (mb_strlen($mutableFragment) > 0 && null === $targetStartPosition) {
            $possibleTarget = mb_substr($mutableFragment, ($targetLength * -1), null, $encoding);

            if ($possibleTarget === $target) {
                $targetStartPosition = $offset - $targetStartPositionOffset;
            } else {
                $mutableFragment = mb_substr(
                    $mutableFragment,
                    0,
                    mb_strlen($mutableFragment) - 1,
                    $encoding
                );
                $targetStartPositionOffset++;
            }
        }

        return null === $targetStartPosition
            ? null
            : $target . mb_substr($fragment, $targetStartPosition, null, $encoding);
    }
}
