<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;

class LocalProxyResource {
    
    const CSS_CONTENT_TYPE = 'text/css';
    const HTML_CONTENT_TYPE = 'text/html';
    
    
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
    private $webResources = array();
    

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
            
            foreach ($this->getStylesheetResources() as $stylesheetResource) {            
                $this->storeWebResource($stylesheetResource);
                $this->updateRootWebResourceStylesheetReference($stylesheetResource, 'file:' . $this->getPath($stylesheetResource));
            }
            
            $this->clearHrefUrlsForExceptionedStylesheets(); 
        }
                
        $this->getConfiguration()->setUrlToValidate('file:' . $this->getPath($rootWebResource));
    }
    
    
    /**
     * Update the href attributes for stylesheets that generated exceptions
     * when being retrieved so that they are ignored by the W3C CSS validator.
     * The W3C CSS validator cannot validate these and will generate unclear
     * errors. We will instead append to the validator output more specific
     * errors.
     */
    private function clearHrefUrlsForExceptionedStylesheets() {        
        foreach ($this->webResourceExceptions as $webResourceException) {
            $this->updateRootWebResourceStylesheetUrl($webResourceException->getRequest()->getUrl(), 'about:blank');
        }        
        
        foreach ($this->curlExceptions as $curlExceptionDetails) {            
            $this->updateRootWebResourceStylesheetUrl($curlExceptionDetails['url'], 'about:blank');
        }          
    }
    
    
    /**
     * 
     * @param string $path
     * @return string
     */
    public function getWebResourceUrlFromPath($path) {
        $webResource = $this->getWebResourceFromLocalPath($path);
        return is_null($webResource) ? null : $webResource->getUrl();
    }
    
    
    /**
     * 
     * @param string $localPath
     * @return \webignition\WebResource\WebResource
     */
    private function getWebResourceFromLocalPath($localPath) {
        foreach ($this->paths as $urlHash => $currentPath) {
            if ($currentPath == $localPath) {
                if (isset($this->webResources[$urlHash])) {
                    return $this->webResources[$urlHash];
                }
            }
        }
        
        return null;        
    }
    
    
    private function updateRootWebResourceStylesheetReference(\webignition\WebResource\WebResource $stylesheetResource, $localPath) {
        if (!$this->isCssResource($stylesheetResource)) {
            return;
        }
        
        if (!$this->isHtmlResource($this->getRootWebResource())) {
            return;
        }
        
        $this->updateRootWebResourceStylesheetUrl($stylesheetResource->getUrl(), $localPath);
    }
    
    
    private function updateRootWebResourceStylesheetUrl($sourceUrl, $newUrl) {
        /* @var $rootWebResource \webignition\WebResource\WebPage\WebPage */
        $rootWebResource = $this->getRootWebResource();

        if (!$this->isHtmlResource($this->getRootWebResource())) {
            return;
        }
        
        $rootDom = new \DOMDocument();
        @$rootDom->loadHTML($rootWebResource->getContent());
        
        $linkElements = $rootDom->getElementsByTagName('link');
        foreach ($linkElements as $linkElement) {
            if ($this->isLinkElementStylesheetElementWithHrefAttribute($linkElement)) {
                $hrefAttribute = trim($linkElement->getAttribute('href'));
                $possibleInDocumentStylesheetUrls = $this->getPossibleStylesheetUrlsFromHref($hrefAttribute);                
                
                foreach ($possibleInDocumentStylesheetUrls as $stylesheetUrl) {
                    if ($stylesheetUrl == $sourceUrl) {
                        $rootWebResource->setContent(str_replace(array(
                            'href="'.$hrefAttribute.'"',
                            'href=\''.$hrefAttribute.'\''
                        ), 'href="'.$newUrl.'"', $rootWebResource->getContent()));
                    }                    
                }
            }
        }

        $this->getConfiguration()->setContentToValidate($rootWebResource->getContent());
        $this->storeWebResource($rootWebResource);        
    }
    
    
    private function getPossibleStylesheetUrlsFromHref($href) {
        $absoluteUrlDeriver = new AbsoluteUrlDeriver(
            $href,
            $this->getRootWebResource()->getUrl()
        );
        
        $givenUrl = (string)$absoluteUrlDeriver->getAbsoluteUrl();        
        $decodedUrl = rawurldecode($givenUrl);
        
        if ($givenUrl == $decodedUrl) {
            return array(
                $givenUrl
            );
        }
        
        return array(
            $givenUrl,
            $decodedUrl
        );
    }
    
    
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
    
    
    /**
     * 
     * @return \webignition\WebResource\WebResource[]
     */
    private function getStylesheetResources() {
        $stylesheetResources = array();
        
        foreach ($this->webResources as $webResource) {
            if ($this->isCssResource($webResource)) {
                $stylesheetResources[] = $webResource;
            }
        }
        
        return $stylesheetResources;
    }
    
    
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
            $this->getWebResource($stylesheetUrl);
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
        file_put_contents($this->getPath($resource), $resource->getContent());
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $resource
     * @return boolean
     */    
    private function isHtmlResource(\webignition\WebResource\WebResource $resource) {
        return $resource->getContentType()->getTypeSubtypeString() === self::HTML_CONTENT_TYPE;
    }    
    
    
    /**
     * 
     * @param \webignition\WebResource\WebResource $resource
     * @return boolean
     */
    private function isCssResource(\webignition\WebResource\WebResource $resource) {
        return $resource->getContentType()->getTypeSubtypeString() === self::CSS_CONTENT_TYPE;
    }
    
    
    /**
     * 
     * @return \webignition\WebResource\WebResource
     */
    public function getRootWebResource() {
        if (!$this->hasRootWebResource() && $this->getConfiguration()->hasContentToValidate()) {             
            $this->webResources[$this->getUrlHash($this->getRootWebResourceUrl())] = $this->deriveRootWebResourceFromContentToValidate();
        }
        
        return $this->getWebResource($this->getRootWebResourceUrl());
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
    private function getWebResource($url) {        
        try {
            if (!$this->hasWebResource($url)) {                               
                $request = clone $this->getConfiguration()->getBaseRequest();            
                $request->setUrl($url);
                
                $this->webResources[$this->getUrlHash($url)] = $this->getConfiguration()->getWebResourceService()->get($request);     
            }            
        } catch (\webignition\WebResource\Exception\Exception $webResourceException) {            
            if ($url === $this->getRootWebResourceUrl()) {
                throw $webResourceException;
            }
            
            $this->webResourceExceptions[$this->getUrlHash($url)] = $webResourceException;
            return null;
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            if ($url === $this->getRootWebResourceUrl()) {
                throw $curlException;
            }
            
            $this->curlExceptions[$this->getUrlHash($url)] = array(
                'url' => $url,
                'exception' => $curlException
            );
            return null;            
        }
        
        return $this->webResources[$this->getUrlHash($url)];
    }
    
    
    /**
     * 
     * @param string $url
     * @return boolean
     */
    private function hasWebResource($url) {
        return isset($this->webResources[$this->getUrlHash($url)]);
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasRootWebResource() {
        return isset($this->webResources[$this->getUrlHash($this->getRootWebResourceUrl())]);
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
            unset($this->webResources[$webResourceHash]);
        }
    }
    
}