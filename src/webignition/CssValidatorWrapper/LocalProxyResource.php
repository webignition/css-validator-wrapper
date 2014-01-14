<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\WebResource\Exception\Exception as WebResourceException;

class LocalProxyResource {
    
    const CSS_CONTENT_TYPE = 'text/css';
    const HTML_CONTENT_TYPE = 'text/html';
    
    
    /**
     * Translates local file paths to their relevant resource URLs
     * 
     * @var array
     */
    private $localPathToResourceUrlMap = array();
    
    
    /**
     *
     * @var Configuration
     */
    private $configuration;
    
    
    /**
     *
     * @var Configuration
     */
    private $sourceConfiguration;
    
    
    /**
     *
     * @var string
     */
    private $paths = array();
    
    
    /**
     *
     * @var \webignition\WebResource\WebResource[]
     */
    private $linkedResources = array();
    

    /**
     *
     * @var \webignition\WebResource\Exception\Exception[]
     */    
    private $webResourceExceptions = array();
    
    
    /**
     *
     * @var \Guzzle\Http\Exception\CurlException[]
     */    
    private $curlExceptions = array();
    
    
    /**
     *
     * @var mixed[]
     */
    private $responses = array();
    
    
    /**
     *
     * @var \webignition\WebResource\WebResource
     */
    private $rootWebResource = null;
        
    
    /**
     * 
     * @param \webignition\CssValidatorWrapper\Configuration\Configuration $sourceConfiguration
     */
    public function __construct(Configuration $sourceConfiguration) {
        $this->sourceConfiguration = $sourceConfiguration;
        $this->configuration = clone $this->sourceConfiguration;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasWebResourceExceptions() {
        return count($this->webResourceExceptions) > 0;
    }
    
    
    /**
     * 
     * @return \webignition\WebResource\Exception\Exception[]
     */
    public function getWebResourceExceptions() {
        return $this->webResourceExceptions;
    }
    
    
    /**
     * 
     * @return array
     */
    public function hasCurlExceptions() {
        return count($this->curlExceptions) > 0;
    }
    
    
    /**
     * 
     * @return \Guzzle\Http\Exception\CurlException[]
     */
    public function getCurlExceptions() {
        return $this->curlExceptions;
    }
    
    
    /**
     * 
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     */
    protected function getSourceConfiguration() {
        return $this->sourceConfiguration;
    }
    
    
    public function prepare() {        
        $rootWebResource = $this->getRootWebResource();        
        $this->storeWebResource($rootWebResource);
        
        if ($this->isHtmlResource($rootWebResource)) {
            $this->retrieveStylesheetResources();

            foreach ($this->responses as $responseIndex => $response) {                
                if ($response instanceof \webignition\WebResource\WebResource) {
                    $this->storeWebResource($response);                 
                    $this->updateRootWebResourceStylesheetUrl($responseIndex, 'file:' . $this->getPath($response));
                } else {
                    $this->updateRootWebResourceStylesheetUrl($responseIndex, 'about:blank');
                }              
            }
        }
                
        $this->getConfiguration()->setUrlToValidate('file:' . $this->getPath($rootWebResource));
    }
    
    
    /**
     * 
     * @param string $path
     * @return string
     */
    public function getWebResourceUrlFromPath($path) {        
        $pathHash = $this->getLocalPathHash($path);        
        return (isset($this->localPathToResourceUrlMap[$pathHash])) ? $this->localPathToResourceUrlMap[$pathHash] : null;
    }
    
    private function updateRootWebResourceStylesheetUrl($index, $newUrl) {
        $rootWebResource = $this->getRootWebResource();
        $currentIndex = 0;
        
        $rootDom = new \DOMDocument();
        @$rootDom->loadHTML($rootWebResource->getContent());
        
        $linkElements = $rootDom->getElementsByTagName('link');
        foreach ($linkElements as $linkElement) {
            if ($this->isLinkElementStylesheetElementWithHrefAttribute($linkElement)) {
                $hrefAttribute = trim($linkElement->getAttribute('href')); 
                
                if ($currentIndex === $index) {
                    $rootWebResource->setContent(str_replace(array(
                        'href="'.$hrefAttribute.'"',
                        'href=\''.$hrefAttribute.'\''
                    ), 'href="'.$newUrl.'"', $rootWebResource->getContent()));                    
                }
                
                $currentIndex++;
            }
        }
        
        $this->getConfiguration()->setContentToValidate($rootWebResource->getContent());
        $this->storeWebResource($rootWebResource);
    }
    
    
//    private function getPossibleStylesheetUrlsFromHref($href) {
//        $absoluteUrlDeriver = new AbsoluteUrlDeriver(
//            $href,
//            $this->getRootWebResource()->getUrl()
//        );
//        
//        $absoluteUrl = $absoluteUrlDeriver->getAbsoluteUrl();
////        $protocolRelativeAbsoluteUrl = clone $absoluteUrl;
////        if ($protocolRelativeAbsoluteUrl->hasScheme()) {
////            $protocolRelativeAbsoluteUrl->setScheme(null);
////        }
//        
//        
//                
//        $givenUrl = (string)$absoluteUrl;        
//        $decodedUrl = rawurldecode($givenUrl);
//        
//        // need:
//        // given
//        // decoded given
//        // protocol relative
//        // decoded protocol relative
//        // .. and each with potential equals on the end
//        
//        if ($givenUrl == $decodedUrl) {
//            return array(
//                $givenUrl,
//                //$givenUrl . '=',
//                //(string)$protocolRelativeAbsoluteUrl
//            );
//        }
//        
//        return array(
//            $givenUrl,
//            $decodedUrl,
//            //(string)$protocolRelativeAbsoluteUrl
//        );
//    }
    
    
    /**
     * 
     * @param \DOMelement $domElement
     * @return boolean
     */
    private function isLinkElementStylesheetElementWithHrefAttribute(\DOMelement $domElement) {
        if (!$domElement->hasAttribute('rel')) {
            return false;
        }
        
        if ($domElement->getAttribute('rel') != 'stylesheet') {
            return false;
        }
        
        if (!$domElement->hasAttribute('href')) {
            return false;
        }
        
        return trim($domElement->getAttribute('href')) != '';        
    }
    
    
//    /**
//     * 
//     * @return \webignition\WebResource\WebResource[]
//     */
//    private function getLinkedResources() {
//        return $this->linkedResources;
//    }
    
    
    private function retrieveStylesheetResources() {        
        /* @var $rootWebResource \webignition\WebResource\WebPage\WebPage */
        $rootWebResource = $this->getRootWebResource();            
        
        if (!$this->isHtmlResource($this->getRootWebResource())) {
            return;
        }
        
        if (!$rootWebResource instanceof \webignition\WebResource\WebPage\WebPage) {
            $rootWebResource = $this->translateHtmlWebResourceToWebPage($rootWebResource);
        }
        
        $stylesheetUrls = $this->findStylesheetUrls($rootWebResource);
        foreach ($stylesheetUrls as $stylesheetUrl) {            
            $this->getLinkedResource($stylesheetUrl);
        }
    }
    
    
    private function findStylesheetUrls(\webignition\WebResource\WebPage\WebPage $webPage) {
        $stylesheetUrls = array();
        
        $rootDom = new \DOMDocument();
        @$rootDom->loadHTML($webPage->getContent());
        
        $linkElements = $rootDom->getElementsByTagName('link');
        foreach ($linkElements as $linkElement) {
            if ($this->isLinkElementStylesheetElementWithHrefAttribute($linkElement)) {
                $hrefAttribute = trim($linkElement->getAttribute('href'));
                $absoluteUrlDeriver = new AbsoluteUrlDeriver(
                    $hrefAttribute,
                    $webPage->getUrl()
                );
                
                $stylesheetUrl = (string)$absoluteUrlDeriver->getAbsoluteUrl();
                
                if (!in_array($stylesheetUrl, $stylesheetUrls)) {
                    $stylesheetUrls[] = $stylesheetUrl;
                }
            }
        } 
        
        return $stylesheetUrls;
    }
    
    
    private function translateHtmlWebResourceToWebPage(\webignition\WebResource\WebResource $webResource) {
        $webPage = new \webignition\WebResource\WebPage\WebPage();
        $webPage->setContent($webResource->getContent());
        $webPage->setContentType($webResource->getContentType());
        $webPage->setUrl($webResource->getUrl());
        
        return $webPage;        
    }
    
    
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $resource
     */
    private function storeWebResource(\webignition\WebResource\WebResource $resource) {        
        $path = $this->getPath($resource);
        
        file_put_contents($path, $resource->getContent());
        $this->setLocalPathToResourceUrlMapping($path, $resource);
        
    }
    
    
    /**
     * 
     * @param string $path
     * @param \webignition\WebResource\WebResource $resource
     */
    private function setLocalPathToResourceUrlMapping($path, \webignition\WebResource\WebResource $resource) {
        $this->localPathToResourceUrlMap[$this->getLocalPathHash($path)] = $resource->getUrl();
    }
    
    
    /**
     * 
     * @param string $path
     * @return string
     */
    private function getLocalPathHash($path) {
        return md5($path);
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $resource
     * @return boolean
     */    
    private function isHtmlResource(\webignition\WebResource\WebResource $resource) {
        return $resource->getContentType()->getTypeSubtypeString() === self::HTML_CONTENT_TYPE;
    }    
    
    
//    /**
//     * 
//     * @param \webignition\WebResource\WebResource $resource
//     * @return boolean
//     */
//    private function isCssResource(\webignition\WebResource\WebResource $resource) {
//        return $resource->getContentType()->getTypeSubtypeString() === self::CSS_CONTENT_TYPE;
//    }
    
    
    /**
     * 
     * @return \webignition\WebResource\WebResource
     */
    public function getRootWebResource() {
        if (!$this->hasRootWebResource()) {             
            if ($this->getConfiguration()->hasContentToValidate()) {
                $this->rootWebResource = $this->deriveRootWebResourceFromContentToValidate();            
            } else {                           
                $request = clone $this->getConfiguration()->getBaseRequest();            
                $request->setUrl($this->getRootWebResourceUrl());
                
                

                $this->rootWebResource = $this->getConfiguration()->getWebResourceService()->get($request);
            }
        }
        
        return $this->rootWebResource;
    }
    
    
    /**
     * 
     * @return \webignition\WebResource\WebResource
     */
    private function deriveRootWebResourceFromContentToValidate() {               
        return $this->getConfiguration()->getWebResourceService()->create(
            $this->getConfiguration()->getUrlToValidate(),
            $this->getConfiguration()->getContentToValidate(),
            $this->deriveRootWebResourceContentTypeFromContentToValidate()
        );
    }
    
    
    /**
     * 
     * @return string
     */
    private function deriveRootWebResourceContentTypeFromContentToValidate() {
        if (strip_tags($this->getConfiguration()->getContentToValidate()) !== $this->getConfiguration()->getContentToValidate()) {
            return 'text/html';
        }
        
        return 'text/css';
    }
    
    
    /**
     * 
     * @return string
     */
    public function getRootWebResourceUrl() {
        return $this->sourceConfiguration->getUrlToValidate();
    }
    
    
//    public function getLinkedResourceUrlByIndex($index) {
//        return $this->
//    }
    
    
    /**
     * 
     * @return Configuration
     */
    public function getConfiguration() {
        return $this->configuration;
    }   
    
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $webResource
     * @return string
     */
    private function getPath(\webignition\WebResource\WebResource $webResource) {
        if (!isset($this->paths[$this->getWebResourceUrlHash($webResource)])) {
            $this->paths[$this->getWebResourceUrlHash($webResource)] = $this->generatePath($webResource);
        }
        
        return $this->paths[$this->getWebResourceUrlHash($webResource)];
    }
    

    /**
     * 
     * @param \webignition\WebResource\WebResource $webResource
     * @return string
     */
    protected function generatePath(\webignition\WebResource\WebResource $webResource) {
        return sys_get_temp_dir() . '/' . md5($webResource->getUrl() . microtime(true)) . '.' . $this->getPathExtension($webResource);
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $webResource
     * @return string
     */
    protected function getPathExtension(\webignition\WebResource\WebResource $webResource) {
        return (string)$webResource->getContentType()->getSubtype();
    }
    
    /**
     * 
     * @return \webignition\WebResource\WebResource
     */
    private function getLinkedResource($url) {        
        try {
            if (!$this->hasLinkedResource($url)) {                               
                $request = clone $this->getConfiguration()->getBaseRequest();            
                $request->setUrl($url);
                
                $resource = $this->getConfiguration()->getWebResourceService()->get($request);
                
                $this->linkedResources[$this->getUrlHash($url)] = $resource;
                $this->responses[] = $resource;
            }            
        } catch (\webignition\WebResource\Exception\Exception $webResourceException) {                        
            $this->webResourceExceptions[$this->getUrlHash($url)] = $webResourceException;
            $this->responses[] = $webResourceException;
            return null;
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {            
            $this->curlExceptions[$this->getUrlHash($url)] = array(
                'url' => $url,
                'exception' => $curlException
            );
            
            $this->responses[] = $curlException;
            
            return null;            
        }
        
        return $this->linkedResources[$this->getUrlHash($url)];
    }
    
    
    /**
     * 
     * @param string $url
     * @return boolean
     */
    private function hasLinkedResource($url) {
        return isset($this->linkedResources[$this->getUrlHash($url)]);
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasRootWebResource() {
        return !is_null($this->rootWebResource);
        
        return isset($this->linkedResources[$this->getUrlHash($this->getRootWebResourceUrl())]);
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $webResource
     * @return string
     */
    private function getWebResourceUrlHash(\webignition\WebResource\WebResource $webResource) {
        return $this->getUrlHash($webResource->getUrl());
    }
    
    
    /**
     * 
     * @param string $url
     * @return string
     */
    private function getUrlHash($url) {
        return md5($url);
    }
    
    
    public function clear() {
        foreach ($this->paths as $webResourceHash => $path) {
            @unlink($path);
            unset($this->linkedResources[$webResourceHash]);
        }
    }
    
}