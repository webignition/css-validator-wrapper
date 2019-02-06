<?php

namespace webignition\CssValidatorWrapper;

class StringUtils
{
    public static function findPreviousAdjoiningStringStartingWith(
        string $content,
        string $target,
        string $encoding,
        ?int $offset = null
    ) {
        $fragment = mb_substr(
            $content,
            0,
            $offset,
            $encoding
        );

        $targetPosition = null;
        $targetPositionOffset = 0;
        $targetLength = mb_strlen($target, $encoding);

        $mutableFragment = $fragment;

        while (mb_strlen($mutableFragment) > 0 && null === $targetPosition) {
            $possibleTarget = mb_substr($mutableFragment, ($targetLength * -1), null, $encoding);

            if ($possibleTarget === $target) {
                $targetPosition = $offset - $targetPositionOffset;
            } else {
                $mutableFragment = mb_substr(
                    $mutableFragment,
                    0,
                    mb_strlen($mutableFragment) - 1,
                    $encoding
                );
                $targetPositionOffset++;
            }
        }

        return null === $targetPosition
            ? null
            : $target . mb_substr($fragment, $targetPosition, null, $encoding);
    }

    public static function findNextAdjoiningStringEndingWith(
        string $content,
        string $target,
        string $encoding,
        ?int $offset = 0
    ) {
        $offset = $offset ?? 0;

        $fragment = mb_substr(
            $content,
            $offset,
            null,
            $encoding
        );

        $targetPosition = null;
        $targetPositionOffset = 0;
        $targetLength = (int) mb_strlen($target, $encoding);

        $mutableFragment = $fragment;

        while (mb_strlen($mutableFragment) > 0 && null === $targetPosition) {
            $possibleTarget = mb_substr($mutableFragment, 0, $targetLength, $encoding);

            if ($possibleTarget === $target) {
                $targetPosition = $offset + $targetPositionOffset;
            } else {
                $mutableFragment = mb_substr(
                    $mutableFragment,
                    1,
                    null,
                    $encoding
                );

                $targetPositionOffset++;
            }
        }

        return null === $targetPosition
            ? null
            : mb_substr($content, $offset, $targetPosition - $offset, $encoding) . $target;
    }
}
