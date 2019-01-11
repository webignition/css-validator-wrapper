<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use webignition\CssValidatorWrapper\StringUtils;

class StringUtilsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider findClosestAdjoiningStringStartingWithDataProvider
     */
    public function testFindClosestAdjoiningStringStartingWith(
        string $content,
        string $target,
        int $offset,
        string $encoding,
        ?string $expectedReturnValue
    ) {
        $this->assertEquals(
            $expectedReturnValue,
            StringUtils::findClosestAdjoiningStringStartingWith($content, $target, $offset, $encoding)
        );
    }

    public function findClosestAdjoiningStringStartingWithDataProvider(): array
    {
        return [
            'empty' => [
                'content' => '',
                'target' => '',
                'offset' => 0,
                'encoding' => 'utf-8',
                'expectedReturnValue' => '',
            ],
            'single stylesheet content, offset within meta element' => [
                'content' => implode("\n", [
                    '<meta href="/style.css" rel="stylesheet">',
                    '<link href="/style.css" rel="stylesheet">',
                ]),
                'target' => '<link',
                'offset' => strlen('<meta href="'),
                'encoding' => 'utf-8',
                'expectedReturnValue' => null,
            ],
            'single stylesheet content, offset within link element' => [
                'content' => implode("\n", [
                    '<meta href="/style.css" rel="stylesheet">',
                    '<link href="/style.css" rel="stylesheet">',
                ]),
                'target' => '<link',
                'offset' => strlen('<meta href="/style.css" rel="stylesheet">' . "\n" . '<link href="'),
                'encoding' => 'utf-8',
                'expectedReturnValue' => '<link href="',
            ],
            'stylesheet partial reference, locate href' => [
                'content' => '<link href="/style.css',
                'target' => 'href',
                'offset' => strlen('<link href="/style.css'),
                'encoding' => 'utf-8',
                'expectedReturnValue' => 'href="/style.css',
            ],
        ];
    }
}
