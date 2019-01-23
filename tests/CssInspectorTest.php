<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use webignition\CssValidatorWrapper\CssInspector;

class CssInspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider findImportsDataProvider
     */
    public function testFindImports(string $css, array $expectedImports)
    {
        $cssInspector = new CssInspector();

        $this->assertEquals($expectedImports, $cssInspector->findImports($css));
    }

    public function findImportsDataProvider(): array
    {
        return [
            'empty' => [
                'css' => '',
                'expectedImports' => [],
            ],
        ];
    }
}
