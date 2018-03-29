<?php

namespace webignition\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\StreamInterface;
use QueryPath\Exception as QueryPathException;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\Retriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResourceInterfaces\InvalidContentTypeExceptionInterface;
use webignition\WebResourceInterfaces\WebPageInterface;
use webignition\WebResourceInterfaces\WebResourceInterface;

class LocalProxyResource
{
    const CSS_CONTENT_TYPE = 'text/css';
    const HTML_CONTENT_TYPE = 'text/html';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Configuration
     */
    private $sourceConfiguration;

    /**
     * @var WebResourceInterface[]
     */
    private $linkedResources = [];

    /**
     * @var HttpException[]
     */
    private $httpExceptions = [];

    /**
     * @var TransportException[]
     */
    private $transportExceptions = [];

    /**
     * @var mixed[]
     */
    private $responses = [];

    /**
     * @var WebResourceRetriever
     */
    private $webResourceRetriever;

    /**
     * @var WebResourceStorage
     */
    private $webResourceStorage;

    /**
     * @param Configuration $sourceConfiguration
     * @param HttpClient $httpClient
     */
    public function __construct(Configuration $sourceConfiguration, HttpClient $httpClient = null)
    {
        $this->sourceConfiguration = $sourceConfiguration;
        $this->configuration = clone $this->sourceConfiguration;

        if (empty($httpClient)) {
            $httpClient = new HttpClient();
        }

        $this->webResourceRetriever = new Retriever(
            $httpClient,
            array_merge(WebPage::getModelledContentTypeStrings(), [HttpResponseFactory::CSS_CONTENT_TYPE]),
            false
        );

        $this->webResourceStorage = new WebResourceStorage();
    }

    /**
     * @return Retriever
     */
    public function getWebResourceRetriever()
    {
        return $this->webResourceRetriever;
    }

    /**
     * @return HttpException[]
     */
    public function getHttpExceptions()
    {
        return $this->httpExceptions;
    }

    /**
     * @return TransportException[]
     */
    public function getTransportExceptions()
    {
        return $this->transportExceptions;
    }

    /**
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws QueryPathException
     * @throws HttpException
     * @throws TransportException
     */
    public function prepare()
    {
        $rootWebResource = $this->getRootWebResource();
        $rootWebResourcePath = $this->webResourceStorage->store($rootWebResource);
        $paths = [$rootWebResourcePath];

        if ($rootWebResource instanceof WebPageInterface) {
            /* @var WebPageInterface $rootWebResource */
            $stylesheetUrls = $this->findStylesheetUrls($rootWebResource);

            foreach ($stylesheetUrls as $stylesheetUrl) {
                $this->retrieveLinkedResource($stylesheetUrl);
            }

            foreach ($this->responses as $responseIndex => $response) {
                $localResourcePath = 'about:blank';

                if ($response instanceof WebResourceInterface) {
                    $localResourcePath = $this->webResourceStorage->store($response);
                    $paths[] = $localResourcePath;
                    $localResourcePath = 'file:' . $localResourcePath;
                }

                $updatedRootWebResource = $this->updateRootWebResourceStylesheetUrl(
                    $rootWebResource,
                    $responseIndex,
                    $localResourcePath
                );

                if (!empty($updatedRootWebResource)) {
                    $rootWebResource = $updatedRootWebResource;
                    $rootWebResourcePath = $this->webResourceStorage->store($rootWebResource);
                }
            }

            $this->configuration->setContentToValidate($rootWebResource->getContent());
        }

        $this->configuration->setUrlToValidate('file:' . $rootWebResourcePath);

        return $paths;
    }

    /**
     * @param WebPageInterface $webPage
     * @param int $index
     * @param string $newUrl
     *
     * @return WebResourceInterface|null
     */
    private function updateRootWebResourceStylesheetUrl(WebPageInterface $webPage, $index, $newUrl)
    {
        $webPageContent = $webPage->getContent();
        $hrefs = $this->findStylesheetHrefs($webPageContent);

        if (!isset($hrefs[$index])) {
            return null;
        }

        $possibleSourceHrefValues = $this->getPossibleSourceHrefValues($hrefs[$index]);

        foreach ($possibleSourceHrefValues as $sourceHrefValue) {
            $possibleSourceHrefAttributeStrings = $this->getPossibleSourceHrefAttributeStrings($sourceHrefValue);

            foreach ($possibleSourceHrefAttributeStrings as $sourceHrefAttribute) {
                if (substr_count($webPageContent, $sourceHrefAttribute)) {
                    $webPageContent = str_replace(
                        $sourceHrefAttribute,
                        'href="'.$newUrl.'"',
                        $webPageContent
                    );
                }
            }
        }

        $newBody = $this->createStreamFromString($webPageContent);

        return $webPage->setBody($newBody);
    }

    /**
     * @param string $content
     *
     * @return StreamInterface
     */
    private function createStreamFromString($content)
    {
        $stream = fopen('php://temp', 'r+');
        if ($content !== '') {
            fwrite($stream, $content);
            fseek($stream, 0);
        }
        return new Stream($stream);
    }

    /**
     * @param string $href
     *
     * @return string[]
     */
    private function getPossibleSourceHrefValues($href)
    {
        $urls = [$href];
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
        return [
            'href="'.$hrefValue.'"',
            'href=\''.$hrefValue.'\''
        ];
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return boolean
     */
    private function isLinkElementStylesheetElementWithHrefAttribute(\DOMelement $domElement)
    {
        $hasStylesheetRelAttribute = $domElement->getAttribute('rel') === 'stylesheet';
        $hasNonEmptyHrefAttribute = !empty(trim($domElement->getAttribute('href')));

        return $hasStylesheetRelAttribute && $hasNonEmptyHrefAttribute;
    }

    /**
     * @param WebPageInterface $webPage
     *
     * @return string[]
     *
     * @throws QueryPathException
     */
    private function findStylesheetUrls(WebPageInterface $webPage)
    {
        $linkFinderConfiguration = new LinkFinderConfiguration([
            LinkFinderConfiguration::CONFIG_KEY_ELEMENT_SCOPE => 'link',
            LinkFinderConfiguration::CONFIG_KEY_SOURCE => $webPage,
            LinkFinderConfiguration::CONFIG_KEY_SOURCE_URL => (string)$webPage->getUri(),
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
     * @param string $webPageContent
     *
     * @return string[]
     */
    private function findStylesheetHrefs($webPageContent)
    {
        $hrefs = [];

        $rootDom = new \DOMDocument();
        @$rootDom->loadHTML($webPageContent);

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
     * @return WebPageInterface|WebResourceInterface
     *
     * @throws InternetMediaTypeParseException
     * @throws HttpException
     * @throws InvalidContentTypeExceptionInterface
     * @throws TransportException
     */
    private function getRootWebResource()
    {
        $contentToValidate = $this->configuration->getContentToValidate();
        $urlToValidate = $this->configuration->getUrlToValidate();

        if (empty($contentToValidate)) {
            $request = new Request('GET', $urlToValidate);

            $rootWebResource = $this->webResourceRetriever->retrieve($request);
        } else {
            $rootWebResource = WebResourceFactory::create(
                $contentToValidate,
                new Uri($urlToValidate)
            );
        }

        return $rootWebResource;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param $url
     *
     * @return null|WebResource
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     */
    private function retrieveLinkedResource($url)
    {
        $urlHash = UrlHasher::create($url);

        try {
            $request = new Request('GET', $url);

            $resource = $this->webResourceRetriever->retrieve($request);


            $this->linkedResources[$urlHash] = $resource;
            $this->responses[] = $resource;
        } catch (HttpException $httpException) {
            $this->httpExceptions[$urlHash] = $httpException;
            $this->responses[] = $httpException;

            return null;
        } catch (TransportException $transportException) {
            $this->transportExceptions[$urlHash] = $transportException;
            $this->responses[] = $transportException;

            return null;
        }

        return $this->linkedResources[$urlHash];
    }

    /**
     * @return WebResourceStorage
     */
    public function getWebResourceStorage()
    {
        return $this->webResourceStorage;
    }

    public function reset()
    {
        $this->webResourceStorage->reset();
        $this->linkedResources = [];
        $this->httpExceptions = [];
        $this->transportExceptions = [];
    }
}
