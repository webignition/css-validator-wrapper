<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\CookiesTest;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;
use webignition\Url\Url;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\Cookie;

abstract class CookiesTest extends BaseTest {
    
    protected $wrapper;
    
    /**
     * 
     * @return array
     */
    abstract protected function getCookies();
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();    
    
    public function setUp() {                       
        $this->setTestFixturePath(get_class($this));
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));

        $cookieJar = new ArrayCookieJar();

        foreach ($this->getCookies() as $cookieData) {
            $cookieJar->add(new Cookie($cookieData));
        }

        $cookiePlugin = new CookiePlugin($cookieJar);

        $this->getHttpClient()->addSubscriber($cookiePlugin);
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setBaseRequest($this->getHttpClient()->get());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);        
        $this->wrapper->enableDeferToParentIfNoRawOutput();
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('no-messages.txt'));
        
        $this->wrapper->validate();
    }
    
    
    public function testCookiesAreSetOnExpectedRequests() {
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldBeSet() as $request) {            
            $this->assertEquals($this->getExpectedCookieValues(), $request->getCookies());
        }
    }
    
    
    public function testCookiesAreNotSetOnExpectedRequests() {        
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldNotBeSet() as $request) {            
            $this->assertEquals(array(), $request->getCookies());
        }
    }    
    

    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->getCookies() as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }
        
        return $nameValueArray;
    }    

    
//    public function testWithHtmlContentWithStylesheets() {         
//        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-with-stylesheets.html'));
//        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());        
//    }
//    
//    public function testLocalProxyContentToValidateHasModifiedStylesheetUrls() {
//        $sourceContentToValidate = file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-with-stylesheets.html');
//        
//        $this->wrapper->getConfiguration()->setContentToValidate($sourceContentToValidate);
//        $this->wrapper->validate();
//        
//        $modifiedContentToValidate = $this->wrapper->getLocalProxyResource()->getConfiguration()->getContentToValidate();
//       
//        $sourceDomLinkUrls = array();
//        
//        $sourceDom = new \DOMDocument();
//        $sourceDom->loadHTML($sourceContentToValidate);
//
//        foreach ($sourceDom->getElementsByTagName('link') as $linkElement) {
//            $sourceDomLinkUrls[] = $linkElement->getAttribute('href');
//        }
//        
//        $modifiedDomLinkUrls = array();
//        
//        $modifiedDom = new \DOMDocument();
//        $modifiedDom->loadHTML($modifiedContentToValidate);
//        
//        foreach ($modifiedDom->getElementsByTagName('link') as $linkElement) {
//            $modifiedDomLinkUrls[] = $linkElement->getAttribute('href');
//        }  
//        
//        foreach ($sourceDomLinkUrls as $sourceDomLinkUrl) {
//            $this->assertFalse(in_array($sourceDomLinkUrl, $modifiedDomLinkUrls));
//        }
//        
//        foreach ($modifiedDomLinkUrls as $modifiedDomLinkUrl) {
//            $this->assertFalse(in_array($modifiedDomLinkUrl, $sourceDomLinkUrls));
//            $url = new Url($modifiedDomLinkUrl);
//            $this->assertEquals('file', $url->getScheme());
//        }      
//    }
//
//    public function testWithHtmlContentWithNoStylesheets() {      
//        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content-no-stylesheets.html'));
//        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
//    }        
//    
//    public function testWithCssContent() {      
//        $this->wrapper->getConfiguration()->setContentToValidate(file_get_contents($this->getFixturesDataPath() . '/WebResourceContent/content.css'));
//        $this->assertEquals(0, $this->wrapper->validate()->getErrorCount());
//    }
    
}