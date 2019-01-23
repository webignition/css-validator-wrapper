<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use Sabberworm\CSS\Property\Import;
use webignition\CssValidatorWrapper\CssInspector;

class CssInspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider findImportsDataProvider
     */
    public function testFindImports(string $css, array $expectedImports)
    {
        $cssInspector = new CssInspector();
        $imports = $cssInspector->findImports($css);

        $this->assertEquals($expectedImports, $this->getImportUrls($imports));
    }

    public function findImportsDataProvider(): array
    {
        return [
            'empty' => [
                'css' => '',
                'expectedImports' => [],
            ],
            'no imports' => [
                'css' => 'html {}',
                'expectedImports' => [],
            ],
            'import, double quotes' => [
                'css' => '@import "import.css";',
                'expectedImports' => [
                    'import.css',
                ],
            ],
            'import, single quotes' => [
                'css' => "@import 'import.css';",
                'expectedImports' => [
                    'import.css',
                ],
            ],
            'charset, import' => [
                'css' => "@charset 'utf-8';\n@import 'import.css';",
                'expectedImports' => [
                    'import.css',
                ],
            ],
            'import, css' => [
                'css' => "@import 'import.css';\nhtml {}",
                'expectedImports' => [
                    'import.css',
                ],
            ],
            'charset, import, css' => [
                'css' => "@charset 'utf-8';\n@import 'import.css';\nhtml {}",
                'expectedImports' => [
                    'import.css',
                ],
            ],
            'charset, css' => [
                'css' => "@charset 'utf-8';\nhtml {}",
                'expectedImports' => [],
            ],
            'css, charset' => [
                'css' => "html {}\n@charset 'utf-8';",
                'expectedImports' => [],
            ],
            'css, import' => [
                'css' => "html {}\n@import 'import.css';",
                'expectedImports' => [],
            ],
            'import, import' => [
                'css' => "@import 'import1.css';\n@import 'import2.css';",
                'expectedImports' => [
                    'import1.css',
                    'import2.css',
                ],
            ],
            'charset, import, import' => [
                'css' => "@charset 'utf-8';@import 'import1.css';\n@import 'import2.css';",
                'expectedImports' => [
                    'import1.css',
                    'import2.css',
                ],
            ],
            'charset, import, css, import' => [
                'css' => "@charset 'utf-8';@import 'import1.css';\nhtml{}\n@import 'import2.css';",
                'expectedImports' => [
                    'import1.css',
                ],
            ],
            'import, css, import' => [
                'css' => "@import 'import1.css';\nhtml{}\n@import 'import2.css';",
                'expectedImports' => [
                    'import1.css',
                ],
            ],
        ];
    }

    /**
     * @param Import[] $imports
     *
     * @return string[]
     */
    private function getImportUrls(array $imports): array
    {
        $importUrls = [];

        foreach ($imports as $import) {
            $importUrls[] = $import->getLocation()->getURL()->getString();
        }

        return $importUrls;
    }
}
