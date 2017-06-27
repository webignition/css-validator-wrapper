<?php

namespace webignition\CssValidatorWrapper;

use GuzzleHttp\Message\MessageFactory as HttpMessageFactory;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use GuzzleHttp\Message\ResponseInterface as HttpResponse;
use GuzzleHttp\Exception\ConnectException;
use webignition\GuzzleHttp\Exception\CurlException\Exception as CurlException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\WebResource\Exception\Exception as WebResourceException;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;

class LocalProxyResource
{
    const CSS_CONTENT_TYPE = 'text/css';
    const HTML_CONTENT_TYPE = 'text/html';

    /**
     * Translates local file paths to their relevant resource URLs
     *
     * @var array
     */
    private $localPathToResourceUrlMap = array();

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Configuration
     */
    private $sourceConfiguration;

    /**
     * @var string[]
     */
    private $paths = array();

    /**
     * @var WebResource[]
     */
    private $linkedResources = array();

    /**
     * @var WebResourceException[]
     */
    private $webResourceExceptions = array();

    /**
     * @var CurlException[]
     */
    private $curlExceptions = array();

    /**
     * @var mixed[]
     */
    private $responses = array();

    /**
     * @var WebResource
     */
    private $rootWebResource = null;

    /**
     * @param Configuration $sourceConfiguration
     */
    public function __construct(Configuration $sourceConfiguration)
    {
        $this->sourceConfiguration = $sourceConfiguration;
        $this->configuration = clone $this->sourceConfiguration;
    }

    /**
     * @return boolean
     */
    public function hasWebResourceExceptions()
    {
        return count($this->webResourceExceptions) > 0;
    }

    /**
     * @return WebResourceException[]
     */
    public function getWebResourceExceptions()
    {
        return $this->webResourceExceptions;
    }

    /**
     * @return bool
     */
    public function hasCurlExceptions()
    {
        return count($this->curlExceptions) > 0;
    }

    /**
     * @return CurlException[]
     */
    public function getCurlExceptions()
    {
        return $this->curlExceptions;
    }

    /**
     * @return string[]
     */
    public function prepare()
    {
        $paths = [];

        $rootWebResource = $this->getRootWebResource();
        $paths[] = $this->storeWebResource($rootWebResource);

        if ($this->isHtmlResource($rootWebResource)) {
            $stylesheetUrls = $this->findStylesheetUrls($rootWebResource);
            foreach ($stylesheetUrls as $stylesheetUrl) {
                $this->getLinkedResource($stylesheetUrl);
            }

            foreach ($this->responses as $responseIndex => $response) {
                if ($response instanceof WebResource) {
                    $paths[] = $this->storeWebResource($response);
                    $this->updateRootWebResourceStylesheetUrl($responseIndex, 'file:' . $this->getPath($response));
                } else {
                    $this->updateRootWebResourceStylesheetUrl($responseIndex, 'about:blank');
                }
            }
        }

        $this->getConfiguration()->setUrlToValidate('file:' . $this->getPath($rootWebResource));

        return $paths;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getWebResourceUrlFromPath($path)
    {
        $pathHash = $this->getLocalPathHash($path);

        return isset($this->localPathToResourceUrlMap[$pathHash])
            ? $this->localPathToResourceUrlMap[$pathHash]
            : null;
    }

    /**
     * @param int $index
     * @param string $newUrl
     */
    private function updateRootWebResourceStylesheetUrl($index, $newUrl)
    {
        $rootWebResource = $this->getRootWebResource();
        $hrefs = $this->findStylesheetHrefs($rootWebResource);

        if (isset($hrefs[$index])) {
            $possibleSourceHrefValues = $this->getPossibleSourceHrefValues($hrefs[$index]);

            foreach ($possibleSourceHrefValues as $sourceHrefValue) {
                $possibleSourceHrefAttributeStrings = $this->getPossibleSourceHrefAttributeStrings($sourceHrefValue);

                foreach ($possibleSourceHrefAttributeStrings as $sourceHrefAttribute) {
                    if (substr_count($rootWebResource->getContent(), $sourceHrefAttribute)) {
                        $rootWebResource->setContent(str_replace(
                            $sourceHrefAttribute,
                            'href="'.$newUrl.'"',
                            $rootWebResource->getContent()
                        ));
                    }
                }
            }
        }

        $this->getConfiguration()->setContentToValidate($rootWebResource->getContent());
        $this->storeWebResource($rootWebResource);
    }

    /**
     * @param string $href
     *
     * @return string[]
     */
    private function getPossibleSourceHrefValues($href)
    {
        $urls = array($href);
        if (substr_count($href, '&')) {
            $urls[] = str_replace('&', '&amp;', $href);
        }

        return $urls;
    }

    /**
     * @param string $hrefValue
     *
     * @return string[]
     */
    private function getPossibleSourceHrefAttributeStrings($hrefValue)
    {
        return array(
            'href="'.$hrefValue.'"',
            'href=\''.$hrefValue.'\''
        );
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return boolean
     */
    private function isLinkElementStylesheetElementWithHrefAttribute(\DOMelement $domElement)
    {
        $hasStylesheetRelAttribute = $domElement->getAttribute('rel') == 'stylesheet';
        $hasNonEmptyHrefAttribute = !empty(trim($domElement->getAttribute('href')));

        return $hasStylesheetRelAttribute && $hasNonEmptyHrefAttribute;
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    private function findStylesheetUrls(WebPage $webPage)
    {
        $linkFinderConfiguration = new LinkFinderConfiguration([
            LinkFinderConfiguration::CONFIG_KEY_ELEMENT_SCOPE => 'link',
            LinkFinderConfiguration::CONFIG_KEY_SOURCE => $webPage,
            LinkFinderConfiguration::CONFIG_KEY_SOURCE_URL => $webPage->getUrl(),
            LinkFinderConfiguration::CONFIG_KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
            LinkFinderConfiguration::CONFIG_KEY_ATTRIBUTE_SCOPE_NAME => 'rel',
            LinkFinderConfiguration::CONFIG_KEY_ATTRIBUTE_SCOPE_VALUE => 'stylesheet',
            LinkFinderConfiguration::CONFIG_KEY_IGNORE_EMPTY_HREF => true,
        ]);
        $linkFinder = new HtmlDocumentLinkUrlFinder();
        $linkFinder->setConfiguration($linkFinderConfiguration);

        return $linkFinder->getUniqueUrls();
    }

    /**
     * @param WebPage $webPage
     *
     * @return string[]
     */
    private function findStylesheetHrefs(WebPage $webPage)
    {
        $hrefs = [];

        $rootDom = new \DOMDocument();
        @$rootDom->loadHTML($webPage->getContent());

        $linkElements = $rootDom->getElementsByTagName('link');
        foreach ($linkElements as $linkElement) {
            if ($this->isLinkElementStylesheetElementWithHrefAttribute($linkElement)) {
                /* @var $linkElement \DOMElement */
                $hrefs[] = trim($linkElement->getAttribute('href'));
            }
        }

        return $hrefs;
    }

    /**
     * @param WebResource $resource
     *
     * @return string
     */
    private function storeWebResource(WebResource $resource)
    {
        $path = $this->getPath($resource);

        file_put_contents($path, $resource->getContent());

        $this->localPathToResourceUrlMap[$this->getLocalPathHash($path)] = $resource->getUrl();

        return $path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getLocalPathHash($path)
    {
        return md5($path);
    }

    /**
     * @param WebResource $resource
     * @return boolean
     */
    private function isHtmlResource(WebResource $resource)
    {
        return $resource->getContentType()->getTypeSubtypeString() === self::HTML_CONTENT_TYPE;
    }

    /**
     * @return WebResource
     */
    public function getRootWebResource()
    {
        if (!$this->hasRootWebResource()) {
            if ($this->getConfiguration()->hasContentToValidate()) {
                $this->rootWebResource = $this->deriveRootWebResourceFromContentToValidate();
            } else {
                $request = $this->getConfiguration()->getHttpClient()->createRequest(
                    'GET',
                    $this->getRootWebResourceUrl()
                );

                $this->rootWebResource = $this->getConfiguration()->getWebResourceService()->get($request);
            }
        }

        return $this->rootWebResource;
    }

    /**
     * @return WebResource
     */
    private function deriveRootWebResourceFromContentToValidate()
    {
        return $this->getConfiguration()->getWebResourceService()->create(
            $this->deriveRootWebResourceHttpResponseFromContentToValidate()
        );
    }

    /**
     * @return HttpResponse
     */
    private function deriveRootWebResourceHttpResponseFromContentToValidate()
    {
        $httpResponse = $this->getHttpResponseFromMessage(
            "HTTP/1.0 200 OK\nContent-Type: "
            . $this->deriveRootWebResourceContentTypeFromContentToValidate()
            . "\n\n"
            . $this->getConfiguration()->getContentToValidate()
        );
        $httpResponse->setEffectiveUrl($this->getConfiguration()->getUrlToValidate());

        return $httpResponse;
    }

    /**
     * @return string
     */
    private function deriveRootWebResourceContentTypeFromContentToValidate()
    {
        $contentToValidate = $this->getConfiguration()->getContentToValidate();

        if (strip_tags($contentToValidate) !== $contentToValidate) {
            return 'text/html';
        }

        return 'text/css';
    }

    /**
     * @return string
     */
    public function getRootWebResourceUrl()
    {
        return $this->sourceConfiguration->getUrlToValidate();
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param WebResource $webResource
     *
     * @return string
     */
    private function getPath(WebResource $webResource)
    {
        if (!isset($this->paths[$this->getWebResourceUrlHash($webResource)])) {
            $this->paths[$this->getWebResourceUrlHash($webResource)] = $this->generatePath($webResource);
        }

        return $this->paths[$this->getWebResourceUrlHash($webResource)];
    }

    /**
     * @param WebResource $webResource
     *
     * @return string
     */
    private function generatePath(WebResource $webResource)
    {
        return sys_get_temp_dir()
            . '/'
            . md5($webResource->getUrl() . microtime(true))
            . '.'
            . $this->getPathExtension($webResource);
    }

    /**
     * @param WebResource $webResource
     *
     * @return string
     */
    private function getPathExtension(WebResource $webResource)
    {
        return (string)$webResource->getContentType()->getSubtype();
    }

    /**
     * @param $url
     *
     * @return null|WebResource
     */
    private function getLinkedResource($url)
    {
        try {
            if (!$this->hasLinkedResource($url)) {
                $request = $this->getConfiguration()->getHttpClient()->createRequest(
                    'GET',
                    $url
                );

                $resource = $this->getConfiguration()->getWebResourceService()->get($request);

                $this->linkedResources[$this->getUrlHash($url)] = $resource;
                $this->responses[] = $resource;
            }
        } catch (WebResourceException $webResourceException) {
            $this->webResourceExceptions[$this->getUrlHash($url)] = $webResourceException;
            $this->responses[] = $webResourceException;
            return null;
        } catch (ConnectException $connectException) {
            $curlExceptionFactory = new CurlExceptionFactory();
            if ($curlExceptionFactory->isCurlException($connectException)) {
                $curlException = $curlExceptionFactory->fromConnectException($connectException);

                $this->curlExceptions[$this->getUrlHash($url)] = array(
                    'url' => $url,
                    'exception' => $curlException
                );

                $this->responses[] = $curlException;
                return null;
            }
        }

        return $this->linkedResources[$this->getUrlHash($url)];
    }

    /**
     * @param string $url
     *
     * @return boolean
     */
    private function hasLinkedResource($url)
    {
        return isset($this->linkedResources[$this->getUrlHash($url)]);
    }

    /**
     * @return boolean
     */
    private function hasRootWebResource()
    {
        return !is_null($this->rootWebResource);
    }

    /**
     * @param WebResource $webResource
     *
     * @return string
     */
    private function getWebResourceUrlHash(WebResource $webResource)
    {
        return $this->getUrlHash($webResource->getUrl());
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getUrlHash($url)
    {
        return md5($url);
    }

    public function clear()
    {
        foreach ($this->paths as $webResourceHash => $path) {
            @unlink($path);
            unset($this->linkedResources[$webResourceHash]);
        }
    }

    /**
     * @param $message
     *
     * @return HttpResponse
     */
    private function getHttpResponseFromMessage($message)
    {
        $factory = new HttpMessageFactory();
        return $factory->fromMessage($message);
    }
}
