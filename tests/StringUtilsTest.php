<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests;

use webignition\CssValidatorWrapper\StringUtils;

class StringUtilsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider findPreviousAdjoiningStringStartingWithDataProvider
     */
    public function testFindPreviousAdjoiningStringStartingWith(
        string $content,
        string $target,
        string $encoding,
        ?int $offset,
        ?string $expectedReturnValue
    ) {
        $this->assertEquals(
            $expectedReturnValue,
            StringUtils::findPreviousAdjoiningStringStartingWith($content, $target, $encoding, $offset)
        );
    }

    public function findPreviousAdjoiningStringStartingWithDataProvider(): array
    {
        return [
            'empty' => [
                'content' => '',
                'target' => '',
                'encoding' => 'utf-8',
                'offset' => null,
                'expectedReturnValue' => '',
            ],
            'single stylesheet content, offset within meta element' => [
                'content' => implode("\n", [
                    '<meta href="/style.css" rel="stylesheet">',
                    '<link href="/style.css" rel="stylesheet">',
                ]),
                'target' => '<link',
                'encoding' => 'utf-8',
                'offset' => strlen('<meta href="'),
                'expectedReturnValue' => null,
            ],
            'single stylesheet content, offset within link element' => [
                'content' => implode("\n", [
                    '<meta href="/style.css" rel="stylesheet">',
                    '<link href="/style.css" rel="stylesheet">',
                ]),
                'target' => '<link',
                'encoding' => 'utf-8',
                'offset' => strlen('<meta href="/style.css" rel="stylesheet">' . "\n" . '<link href="'),
                'expectedReturnValue' => '<link href="',
            ],
            'stylesheet partial reference, locate href' => [
                'content' => '<link href="/style.css',
                'target' => 'href',
                'encoding' => 'utf-8',
                'offset' => null,
                'expectedReturnValue' => 'href="/style.css',
            ],
        ];
    }

    /**
     * @dataProvider findNextAdjoiningStringEndingWithDataProvider
     */
    public function testFindNextAdjoiningStringEndingWith(
        string $content,
        string $target,
        string $encoding,
        ?int $offset,
        ?string $expectedReturnValue
    ) {
        $this->assertEquals(
            $expectedReturnValue,
            StringUtils::findNextAdjoiningStringEndingWith($content, $target, $encoding, $offset)
        );
    }

    public function findNextAdjoiningStringEndingWithDataProvider(): array
    {
        return [
            'empty' => [
                'content' => '',
                'target' => '',
                'encoding' => 'utf-8',
                'offset' => null,
                'expectedReturnValue' => '',
            ],
            'single-line link element' => [
                'content' => '<link href="" rel="stylesheet">',
                'target' => 'stylesheet',
                'encoding' => 'utf-8',
                'offset' => strlen('<link href=""'),
                'expectedReturnValue' => ' rel="stylesheet',
            ],
            'single stylesheet content, offset within link element' => [
                'content' => implode("\n", [
                    '<meta href="/style.css" w="x" rel="stylesheet">',
                    '<link href="/style.css" y="z" rel="stylesheet">',
                ]),
                'target' => 'stylesheet',
                'encoding' => 'utf-8',
                'offset' => strlen(
                    '<meta href="/style.css" w="x" rel="stylesheet">' . "\n" . '<link href="/style.css'
                ),
                'expectedReturnValue' => '" y="z" rel="stylesheet',
            ],
        ];
    }
}
