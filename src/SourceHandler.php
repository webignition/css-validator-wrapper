<?php

namespace webignition\CssValidatorWrapper;

use webignition\WebResource\WebPage\WebPage;

class SourceHandler
{
    private $webPage;
    private $sourceMap;
    private $inspector;
    private $mutator;

    public function __construct(WebPage $webPage, SourceMap $sourceMap)
    {
        $this->webPage = $webPage;
        $this->sourceMap = $sourceMap;

        $this->inspector = new SourceInspector($webPage);
        $this->mutator = new SourceMutator($webPage, $sourceMap, $this->inspector);
    }

    public function getWebPage(): WebPage
    {
        return $this->webPage;
    }

    public function getSourceMap(): SourceMap
    {
        return $this->sourceMap;
    }

    public function getInspector(): SourceInspector
    {
        return $this->inspector;
    }

    public function getMutator(): SourceMutator
    {
        return $this->mutator;
    }
}
