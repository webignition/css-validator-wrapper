<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use webignition\CssValidatorWrapper\SourceHandler;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\CssValidatorWrapper\SourceMap;
use webignition\CssValidatorWrapper\SourceMutator;
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
}
