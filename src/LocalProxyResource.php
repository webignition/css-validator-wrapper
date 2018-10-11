<?php

namespace webignition\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use webignition\CssValidatorWrapper\Configuration\Configuration;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\StreamFactory\StreamFactory;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\Retriever;
use webignition\WebResource\Storage as WebResourceStorage;
use webignition\WebResource\WebPage\WebPage;
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
     * @var StreamFactory
     */
    private $streamFactory;

    /**
     * @var InvalidResponseContentTypeException[]
     */
    private $invalidResponseContentTypeExceptions = [];

    public function __construct(Configuration $sourceConfiguration, HttpClient $httpClient = null)
    {
        $this->sourceConfiguration = $sourceConfiguration;
        $this->configuration = clone $this->sourceConfiguration;

        if (empty($httpClient)) {
            $httpClient = new HttpClient();
        }

        $this->webResourceRetriever = new Retriever(
            $httpClient,
            array_merge(WebPage::getModelledContentTypeStrings(), [self::CSS_CONTENT_TYPE]),
            false
        );

        $this->webResourceStorage = new WebResourceStorage();
        $this->streamFactory = new StreamFactory();
    }

    /**
     * @return HttpException[]
     */
    public function getHttpExceptions(): array
    {
        return $this->httpExceptions;
    }

    /**
     * @return TransportException[]
     */
    public function getTransportExceptions(): array
    {
        return $this->transportExceptions;
    }

    /**
     * @return InvalidResponseContentTypeException[]
     */
    public function getInvalidResponseContentTypeExceptions(): array
    {
        return $this->invalidResponseContentTypeExceptions;
    }

    /**
     * @return string[]
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws TransportException
     * @throws UnparseableContentTypeException
     */
    public function prepare(): array
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

    private function updateRootWebResourceStylesheetUrl(
        WebPageInterface $webPage,
        int $index,
        string $newUrl
    ): WebResourceInterface {
        $webPageContent = $webPage->getContent();
        $hrefs = $this->findStylesheetHrefs($webPageContent);

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

        return $webPage->setContent($webPageContent, $this->streamFactory);
    }

    /**
     * @param string $href
     *
     * @return string[]
     */
    private function getPossibleSourceHrefValues(string $href): array
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
    private function getPossibleSourceHrefAttributeStrings(string $hrefValue): array
    {
        return [
            'href="'.$hrefValue.'"',
            'href=\''.$hrefValue.'\''
        ];
    }

    private function isLinkElementStylesheetElementWithHrefAttribute(\DOMelement $domElement): bool
    {
        $hasStylesheetRelAttribute = $domElement->getAttribute('rel') === 'stylesheet';
        $hasNonEmptyHrefAttribute = !empty(trim($domElement->getAttribute('href')));

        return $hasStylesheetRelAttribute && $hasNonEmptyHrefAttribute;
    }

    /**
     * @param WebPageInterface $webPage
     *
     * @return string[]
     */
    private function findStylesheetUrls(WebPageInterface $webPage): array
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
    private function findStylesheetHrefs(string $webPageContent): array
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
     * @throws TransportException
     * @throws InvalidContentTypeException
     * @throws InvalidResponseContentTypeException
     * @throws UnparseableContentTypeException
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

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @param string $url
     *
     * @return WebResourceInterface|null
     *
     * @throws InternetMediaTypeParseException
     */
    private function retrieveLinkedResource(string $url): ?WebResourceInterface
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
        } catch (InvalidResponseContentTypeException $invalidContentTypeException) {
            $this->invalidResponseContentTypeExceptions[$urlHash] = $invalidContentTypeException;
            $this->responses[] = $invalidContentTypeException;

            return null;
        }

        return $this->linkedResources[$urlHash];
    }

    public function getWebResourceStorage(): WebResourceStorage
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
