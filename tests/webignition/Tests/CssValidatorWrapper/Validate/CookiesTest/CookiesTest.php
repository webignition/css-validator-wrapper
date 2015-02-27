<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\CookiesTest;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\Tests\CssValidatorWrapper\BaseTest;
use webignition\Url\Url;
use GuzzleHttp\Subscriber\Cookie as HttpCookieSubscriber;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Message\RequestInterface as HttpRequest;

abstract class CookiesTest extends BaseTest {
    
    protected $wrapper;
    
    /**
     * 
     * @return array
     */
    abstract protected function getCookies();
    
    /**
     * 
     * @return HttpRequest[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    
    
    /**
     * 
     * @return HttpRequest[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();    
    
    public function setUp() {                       
        $this->setTestFixturePath(get_class($this));
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));

        $cookieJar = new CookieJar();

        foreach ($this->getCookies() as $cookieData) {
            $cookieJar->setCookie(new SetCookie($cookieData));
        }

        $this->getHttpClient()->getEmitter()->attach(new HttpCookieSubscriber($cookieJar));
        
        $configuration = new Configuration();
        $configuration->setUrlToValidate('http://example.com/');
        $configuration->setHttpClient($this->getHttpClient());
        
        $this->wrapper = $this->getNewCssValidatorWrapper();
        $this->wrapper->setConfiguration($configuration);        
        $this->wrapper->enableDeferToParentIfNoRawOutput();
        $this->wrapper->setCssValidatorRawOutput($this->getFixture('no-messages.txt'));
        
        $this->wrapper->validate();
    }
    
    
    public function testCookiesAreSetOnExpectedRequests() {
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldBeSet() as $request) {            
            $this->assertEquals($this->getExpectedCookieValues(), $this->getRequestCookieValues($request));
        }
    }
    
    
    public function testCookiesAreNotSetOnExpectedRequests() {        
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldNotBeSet() as $request) {            
            $this->assertEquals(array(), $this->getRequestCookieValues($request));
        }
    }    
    

    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->getCookies() as $cookie) {
            $nameValueArray[$cookie['Name']] = $cookie['Value'];
        }
        
        return $nameValueArray;
    }


    /**
     * @param HttpRequest $request
     * @return array
     */
    private function getRequestCookieValues(HttpRequest $request) {
        if (!$request->hasHeader('Cookie')) {
            return [];
        }
        $cookieStrings = explode(';', $request->getHeader('Cookie'));
        $values = [];
        foreach ($cookieStrings as $cookieString) {
            $cookieString = trim($cookieString);
            $currentValues = explode('=', $cookieString);
            $values[$currentValues[0]] = $currentValues[1];
        }
        return $values;
    }
    
}