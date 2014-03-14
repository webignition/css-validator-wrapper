<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\CookiesTest;

class WithDomainWithPathNoSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'path' => '/foo',
                'name' => 'name1',
                'value' => 'value1'
            )                       
        );         
    }
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return $this->getHttpHistory()->getLastRequest();
    }    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        $requests = array();
        
        foreach ($this->getHttpHistory()->getAll() as $httpTransaction) {
            if ($httpTransaction['request']->getUrl() != 'http://example.com/foo/style3.css') {
                $requests[] = $httpTransaction['request'];
            }
        }
        
        return $requests;
    }
    
    
    
    
}