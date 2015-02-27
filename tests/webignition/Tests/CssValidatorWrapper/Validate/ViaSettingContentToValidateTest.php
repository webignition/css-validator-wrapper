<?php

namespace webignition\Tests\CssValidatorWrapper\Validate;

use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;
use webignition\Url\Url;

class ViaSettingContentToValidateTest extends BaseTest {

    /**
     * @var CssValidatorWrapper
     */
    private $wrapper;
    
    public function setUp() {                       
        $this->setTestFixturePath(__CLASS__);
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setHttpClient($this->getHttpClient());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);        
        $this->wrapper->enableDeferToParentIfNoRawOutput();
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('no-messages.txt'));
    }
    
    public function testWithHtmlContentWithStylesheets() {         
        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-with-stylesheets.html'));
        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());        
    }
    
    public function testLocalProxyContentToValidateHasModifiedStylesheetUrls() {
        $sourceContentToValidate = file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-with-stylesheets.html');
        
        $this->wrapper->getConfiguration()->setContentToValidate($sourceContentToValidate);
        $this->wrapper->validate();
        
        $modifiedContentToValidate = $this->wrapper->getLocalProxyResource()->getConfiguration()->getContentToValidate();
       
        $sourceDomLinkUrls = array();
        
        $sourceDom = new \DOMDocument();
        $sourceDom->loadHTML($sourceContentToValidate);

        foreach ($sourceDom->getElementsByTagName('link') as $linkElement) {
            $sourceDomLinkUrls[] = $linkElement->getAttribute('href');
        }
        
        $modifiedDomLinkUrls = array();
        
        $modifiedDom = new \DOMDocument();
        $modifiedDom->loadHTML($modifiedContentToValidate);
        
        foreach ($modifiedDom->getElementsByTagName('link') as $linkElement) {
            $modifiedDomLinkUrls[] = $linkElement->getAttribute('href');
        }  
        
        foreach ($sourceDomLinkUrls as $sourceDomLinkUrl) {
            $this->assertFalse(in_array($sourceDomLinkUrl, $modifiedDomLinkUrls));
        }
        
        foreach ($modifiedDomLinkUrls as $modifiedDomLinkUrl) {
            $this->assertFalse(in_array($modifiedDomLinkUrl, $sourceDomLinkUrls));
            $url = new Url($modifiedDomLinkUrl);
            $this->assertEquals('file', $url->getScheme());
        }      
    }

    public function testWithHtmlContentWithNoStylesheets() {      
        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-no-stylesheets.html'));
        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
    }        
    
    public function testWithCssContent() {      
        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content.css'));
        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
    }
    
}