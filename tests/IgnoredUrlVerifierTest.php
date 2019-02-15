<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests;

use webignition\CssValidatorWrapper\IgnoredUrlVerifier;

class IgnoredUrlVerifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IgnoredUrlVerifier
     */
    private $ignoredUrlVerifier;

    protected function setUp()
    {
        parent::setUp();

        $this->ignoredUrlVerifier = new IgnoredUrlVerifier();
    }

    /**
     * @dataProvider isUrlIgnoredDataProvider
     */
    public function testIsUrlIgnored(string $url, array $domainsToIgnore, bool $expectedIsIgnored)
    {
        $this->assertEquals(
            $expectedIsIgnored,
            $this->ignoredUrlVerifier->isUrlIgnored($url, $domainsToIgnore)
        );
    }

    public function isUrlIgnoredDataProvider(): array
    {
        return [
            'no domains to ignore' => [
                'url' => 'http://example.com',
                'domainsToIgnore' => [],
                'expectedIsIgnored' => false,
            ],
            'no match' => [
                'url' => 'http://example.com',
                'domainsToIgnore' => [
                    'foo.example.com',
                    'bar.example.com',
                ],
                'expectedIsIgnored' => false,
            ],
            'ascii url matches ascii domain' => [
                'url' => 'http://example.com',
                'domainsToIgnore' => [
                    'example.com',
                ],
                'expectedIsIgnored' => true,
            ],
            'punycode url matches unicode domain' => [
                'url' => 'http://xn--u2u.com',
                'domainsToIgnore' => [
                    '搜.com',
                ],
                'expectedIsIgnored' => true,
            ],
            'unicode url matches punycode domain' => [
                'url' => 'http://搜.com',
                'domainsToIgnore' => [
                    'xn--u2u.com',
                ],
                'expectedIsIgnored' => true,
            ],
        ];
    }
}
