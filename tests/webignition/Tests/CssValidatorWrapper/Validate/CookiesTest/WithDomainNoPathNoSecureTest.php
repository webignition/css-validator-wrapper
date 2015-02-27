<?php

namespace webignition\Tests\CssValidatorWrapper\Validate\CookiesTest;

use GuzzleHttp\Message\RequestInterface as HttpRequest;

class WithDomainNoPathNoSecureTest extends CookiesTest { 
    
    protected function getCookies() {
        return array(
            array(
                'Domain' => '.example.com',
                'Name' => 'name1',
                'Value' => 'value1'
            )                       
        );         
    }
    
    /**
     * 
     * @return HttpRequest[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return $this->getHttpHistory()->getRequests();
    }    
    
    
    /**
     * 
     * @return HttpRequest[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return array();
    }
    
    
    
    
}