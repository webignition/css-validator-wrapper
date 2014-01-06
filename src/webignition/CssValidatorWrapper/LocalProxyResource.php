<?php

namespace webignition\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;

class LocalProxyResource {
    
    const CSS_CONTENT_TYPE = 'text/css';
    const HTML_CONTENT_TYPE = 'text/html';
    
    
    /**
     *
     * @var \Guzzle\Http\Message\Request
     */
    private $baseRequest = null;
    
    
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
     * @var \webignition\WebResource\Service\Service
     */
    private $webResourceService;
    
    
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
     * @param \webignition\CssValidatorWrapper\Configuration\Configuration $sourceConfiguration
     * @param \Guzzle\Http\Message\Request $baseRequest
     */
    public function __construct(Configuration $sourceConfiguration, \Guzzle\Http\Message\Request $baseRequest) {
        $this->sourceConfiguration = $sourceConfiguration;
        $this->configuration = clone $this->sourceConfiguration;
        
        $this->baseRequest = $baseRequest;
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
     * @return \webignition\CssValidatorWrapper\Configuration\Configuration
     */
    protected function getSourceConfiguration() {
        return $this->sourceConfiguration;
    }
    
    
    public function prepare() {
        $rootWebResource = $this->getRootWebResource();
        $this->storeWebResource($rootWebResource);
        
        if (!$this->isHtmlResource($rootWebResource)) {
            return;
        }
        
        if ($this->isHtmlResource($rootWebResource)) {
            $this->retrieveStylesheetResources();
        }
        
        foreach ($this->getStylesheetResources() as $stylesheetResource) {            
            $this->storeWebResource($stylesheetResource);
            $this->updateRootWebResourceStylesheetReference($stylesheetResource, 'file:' . $this->getPath($stylesheetResource));
        }
        
        $this->clearHrefUrlsForExceptionedStylesheets();        
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
        $rootDom->loadHTML($rootWebResource->getContent());
        
        $linkElements = $rootDom->getElementsByTagName('link');
        foreach ($linkElements as $linkElement) {
            if ($this->isLinkElementStylesheetElementWithHrefAttribute($linkElement)) {
                $hrefAttribute = trim($linkElement->getAttribute('href'));
                
                $absoluteUrlDeriver = new AbsoluteUrlDeriver(
                    $hrefAttribute,
                    $rootWebResource->getUrl()
                );
                
                $stylesheetUrl = (string)$absoluteUrlDeriver->getAbsoluteUrl();
                
                if ($stylesheetUrl == $sourceUrl) {
                    $rootWebResource->setContent(str_replace(array(
                        'href="'.$hrefAttribute.'"',
                        'href=\''.$hrefAttribute.'\''
                    ), 'href="'.$newUrl.'"', $rootWebResource->getContent()));
                }
            }
        }

        $this->storeWebResource($rootWebResource);        
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
        
        if (!$domElement->getAttribute('rel') == 'stylesheet') {
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
        
        $stylesheetUrls = array();
        
        $rootWebResource->find('link[rel=stylesheet]')->each(function ($index, \DOMElement $domElement) use ($rootWebResource, &$stylesheetUrls) {
            $hrefAttribute = trim($domElement->getAttribute('href'));
            if ($hrefAttribute !== '') {
                $absoluteUrlDeriver = new AbsoluteUrlDeriver(
                    $hrefAttribute,
                    $rootWebResource->getUrl()
                );
                
                $stylesheetUrl = (string)$absoluteUrlDeriver->getAbsoluteUrl();
                if (!in_array($stylesheetUrl, $stylesheetUrls)) {
                    $stylesheetUrls[] = $stylesheetUrl;
                }
            }
        });
        
        foreach ($stylesheetUrls as $stylesheetUrl) {
            $this->getWebResource($stylesheetUrl);
        }
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
        return $this->getWebResource($this->getRootWebResourceUrl());
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
     * @return \Guzzle\Http\Message\Request $request
     */
    public function getBaseRequest() {
        if (is_null($this->baseRequest)) {
            $client = new \Guzzle\Http\Client;            
            $this->baseRequest = $client->get();
        }
        
        return $this->baseRequest;
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
        return sys_get_temp_dir() . '/' . md5($webResource->getUrl() . microtime(true)) . '.' . $webResource->getContentType()->getSubtype();
    }
    
    
    /**
     * 
     * @return \webignition\WebResource\WebResource
     */
    private function getWebResource($url) {        
        try {
            if (!isset($this->webResources[$this->getUrlHash($url)])) {
                $request = clone $this->getBaseRequest();            
                $request->setUrl($url);
                $request->setAuth($this->getConfiguration()->getHttpAuthUser(), $this->getConfiguration()->getHttpAuthPassword(), 'any');

                $this->webResources[$this->getUrlHash($url)] = $this->getWebResourceService()->get($request);            
            }            
        } catch (\webignition\WebResource\Exception\Exception $webResourceException) {
            if ($url === $this->getRootWebResourceUrl()) {
                throw $webResourceException;
            }
            
            $this->webResourceExceptions[$this->getUrlHash($url)] = $webResourceException;
            return null;
        }
        
        return $this->webResources[$this->getUrlHash($url)];
    }
    
    
    /**
     * 
     * @return \webignition\WebResource\Service\Service
     */
    private function getWebResourceService() {
        if (is_null($this->webResourceService)) {
            $this->webResourceService = new \webignition\WebResource\Service\Service(array(
                'text/html' => 'webignition\WebResource\WebPage\WebPage',
                'application/xhtml+xml' =>'webignition\WebResource\WebPage\WebPage',
                'application/json' => 'webignition\WebResource\JsonDocument\JsonDocument'            
            ));
        }
        
        return $this->webResourceService;
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