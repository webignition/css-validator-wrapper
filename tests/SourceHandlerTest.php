<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use webignition\CssValidatorWrapper\SourceHandler;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMutator;
use webignition\CssValidatorWrapper\Tests\Factory\FixtureLoader;
use webignition\CssValidatorWrapper\Tests\Factory\WebPageFixtureFactory;
use webignition\UrlSourceMap\SourceMap;
use webignition\WebResource\WebPage\WebPage;

class SourceHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $webPage = new WebPage();
        $sourceMap = new SourceMap();

        $sourceHandler = new SourceHandler($webPage, $sourceMap);

        $this->assertInstanceOf(SourceInspector::class, $sourceHandler->getInspector());
        $this->assertInstanceOf(SourceMutator::class, $sourceHandler->getMutator());

        $this->assertSame($webPage, $sourceHandler->getInspector()->getWebPage());
        $this->assertSame($webPage, $sourceHandler->getMutator()->getWebPage());
    }

    /**
     * @dataProvider noContentEncodingChangesDataProvider
     */
    public function testNoContentEncodingChanges(WebPage $webPage)
    {
        $sourceMap = new SourceMap();

        $sourceHandler = new SourceHandler($webPage, $sourceMap);

        $this->assertSame($webPage, $sourceHandler->getWebPage());
    }

    public function noContentEncodingChangesDataProvider(): array
    {
        return [
            'ascii, has valid content encoding' => [
                'webPage' =>  WebPage::createFromContent(
                    FixtureLoader::load('Html/minimal-html5.html')
                ),
            ],
        ];
    }

    /**
     * @dataProvider hasContentEncodingChangesDataProvider
     */
    public function testHasContentEncodingChanges(string $encoding)
    {
        $encodedContent = WebPageFixtureFactory::createMarkupContainingFragment(
            '<meta name="keywords" content="搜">',
            $encoding
        );

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($encodedContent);

        $expectedContent = mb_convert_encoding($encodedContent, 'utf-8', $encoding);

        $sourceHandler = new SourceHandler($webPage, new SourceMap());
        $mutatedWebPage = $sourceHandler->getWebPage();

        $this->assertNotSame($webPage, $mutatedWebPage);
        $this->assertEquals($webPage->getCharacterSet(), $mutatedWebPage->getCharacterSet());
        $this->assertNotEquals($webPage->getCharacterEncoding(), $mutatedWebPage->getCharacterEncoding());
        $this->assertEquals('utf-8', $mutatedWebPage->getCharacterEncoding());
        $this->assertEquals($expectedContent, $mutatedWebPage->getContent());
    }

    public function hasContentEncodingChangesDataProvider(): array
    {
        return [
            // Chinese
            ['encoding' => 'big5'],
            ['encoding' => 'cp936'],
            ['encoding' => 'gbk'],
            ['encoding' => 'gb18030'],
            ['encoding' => 'gb2312'],
            // Japanese
            ['encoding' => 'shift_jis'],
            // Korean
            ['encoding' => 'euc-kr'],
        ];
    }
}
