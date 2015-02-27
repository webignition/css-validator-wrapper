<?php

namespace webignition\Tests\CssValidatorWrapper;

use webignition\CssValidatorWrapper\Mock\Wrapper as MockCssValidatorWrapper;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use GuzzleHttp\Message\MessageFactory as HttpMessageFactory;
use GuzzleHttp\Message\ResponseInterface as HttpResponse;
use GuzzleHttp\Message\Request as HttpRequest;
use GuzzleHttp\Exception\ConnectException;

abstract class BaseTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * 
     * @return \webignition\CssValidatorWrapper\Mock\Wrapper
     */
    public function getNewCssValidatorWrapper() {
        return new MockCssValidatorWrapper();
    }
    
    
    const FIXTURES_BASE_PATH = '/../../../fixtures';
    
    /**
     *
     * @var string
     */
    private $fixturePath = null;    
    
    
    /**
     *
     * @var HttpClient
     */
    private $httpClient = null;    

    /**
     * 
     * @param string $testClass
     * @param string $testMethod
     */
    protected function setTestFixturePath($testClass, $testMethod = null) {
        $this->fixturePath = __DIR__ . self::FIXTURES_BASE_PATH . '/' . str_replace('\\', '/', $testClass);       
        
        if (!is_null($testMethod)) {
            $this->fixturePath .= '/' . $testMethod;
        }
    }    
    
    
    /**
     * 
     * @return string
     */
    protected function getTestFixturePath() {
        return $this->fixturePath;     
    }
    
    
    /**
     * 
     * @param string $fixtureName
     * @return string
     */
    protected function getFixture($fixtureName) {        
        if (file_exists($this->getTestFixturePath() . '/' . $fixtureName)) {
            return file_get_contents($this->getTestFixturePath() . '/' . $fixtureName);
        }
        
        return file_get_contents(__DIR__ . self::FIXTURES_BASE_PATH . '/Common/' . $fixtureName);        
    }
    
    
    protected function setHttpFixtures($fixtures) {
        $this->getHttpClient()->getEmitter()->attach(
            new HttpMockSubscriber($fixtures)
        );
    }
    
    /**
     * 
     * @param array $items Collection of http messages and/or curl exceptions
     * @return array
     */
    protected function buildHttpFixtureSet($items) {
        $fixtures = array();
        
        foreach ($items as $item) {
            switch ($this->getHttpFixtureItemType($item)) {
                case 'httpMessage':
                    $fixtures[] = $this->getHttpResponseFromMessage($item);
                    break;
                
                case 'curlException':
                    $fixtures[] = $this->getCurlExceptionFromCurlMessage($item);                    
                    break;
                
                default:
                    throw new \LogicException();
            }
        }
        
        return $fixtures;
    }
    
    protected function getHttpFixtures($path, $filter = null) {
        $items = array();

        $fixturesDirectory = new \DirectoryIterator($path);
        $fixturePaths = array();
        foreach ($fixturesDirectory as $directoryItem) {
            if ($directoryItem->isFile() && ((!is_array($filter)) || (is_array($filter) && in_array($directoryItem->getFilename(), $filter)))) {                
                $fixturePaths[] = $directoryItem->getPathname();
            }
        }
        
        sort($fixturePaths);        
        
        foreach ($fixturePaths as $fixturePath) {
            $items[] = file_get_contents($fixturePath);
        }
        
        return $this->buildHttpFixtureSet($items);
    }
    
    
    /**
     * 
     * @param string $item
     * @return string
     */
    private function getHttpFixtureItemType($item) {
        if (substr($item, 0, strlen('HTTP')) == 'HTTP') {
            return 'httpMessage';
        }
        
        return 'curlException';
    }    
    
    
    /**
     *
     * @param string $testName
     * @return string
     */
    protected function getFixturesDataPath($testName = null) {
        return (is_null($testName))
            ? $this->fixturePath
            : $this->fixturePath . '/' . $testName;
    } 

    /**
     * @param array $options
     * @return HttpClient
     */
    protected function getHttpClient($options = []) {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($options);
            $this->httpClient->getEmitter()->attach(new HttpHistorySubscriber());
        }
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @return HttpHistorySubscriber|null
     */
    protected function getHttpHistory() {
        $listenerCollections = $this->getHttpClient()->getEmitter()->listeners('complete');
        
        foreach ($listenerCollections as $listener) {
            if ($listener[0] instanceof HttpHistorySubscriber) {
                return $listener[0];
            }
        }
        
        return null;     
    }    
    
    
    /**
     * 
     * @param string $curlMessage
     * @return ConnectException
     */
    private function getCurlExceptionFromCurlMessage($curlMessage) {
        $curlMessageParts = explode(' ', $curlMessage, 2);

        return new ConnectException(
            'cURL error ' . str_replace('CURL/', '', $curlMessageParts[0]) . ': ' . $curlMessageParts[1],
            new HttpRequest('GET', 'http://example.com/')
        );
    }


    /**
     * @param $message
     * @return HttpResponse
     */
    protected function getHttpResponseFromMessage($message) {
        $factory = new HttpMessageFactory();
        return $factory->fromMessage($message);
    }
}