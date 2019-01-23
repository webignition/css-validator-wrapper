<?php

namespace webignition\CssValidatorWrapper;

use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Property\Charset;
use Sabberworm\CSS\Property\Import;

class CssInspector
{
    public function findImports(string $css): array
    {
        $imports = [];
        $css = trim($css);

        if ('' === $css) {
            return $imports;
        }

        $cssParser = new CssParser($css);
        $cssDocument = $cssParser->parse();

        $documentContents = $cssDocument->getContents();

        $hasEncounteredNonImportNonCharset = false;

        $imports = [];

        foreach ($documentContents as $item) {
            if ($item instanceof Import) {
                if (false === $hasEncounteredNonImportNonCharset) {
                    $imports[] = $item;
                }
            } else {
                if (!$item instanceof Charset) {
                    $hasEncounteredNonImportNonCharset = true;
                }
            }
        }

        return $imports;
    }
}
