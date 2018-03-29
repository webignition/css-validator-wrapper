<?php

namespace webignition\Tests\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use phpmock\mockery\PHPMockery;

abstract class AbstractBaseTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'microtime'
        )->andReturn(1);
    }

    /**
     * @param array $httpFixtures
     *
     * @return HttpClient
     */
    protected function createHttpClient(array $httpFixtures)
    {
        $mockHandler = new MockHandler($httpFixtures);
        $httpClient = new HttpClient(['handler' => HandlerStack::create($mockHandler)]);

        return $httpClient;
    }

//    /**
//     * @param array $responseFixtures
//     *
//     * @return HttpClient
//     */
//    protected function createHttpClient($responseFixtures)
//    {
//        $mockHandler = new MockHandler($responseFixtures);
//        $httpClient = new HttpClient(['handler' => HandlerStack::create($mockHandler)]);
//
//        return $httpClient;
//    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function loadHtmlDocumentFixture($name)
    {
        return file_get_contents(__DIR__ . '/fixtures/html-documents/' . $name . '.html');
    }

    /**
     * @param string $contentType
     * @param string $body
     *
     * @return string
     */
    protected function createHttpFixture($contentType, $body)
    {
        return "HTTP/1.1 200 OK\nContent-type:" . $contentType . "\n\n" . $body;
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
