<?php

namespace webignition\Tests\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use phpmock\mockery\PHPMockery;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
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
     * @param array $responseFixtures
     *
     * @return HttpClient
     */
    protected function createHttpClient($responseFixtures)
    {
        $httpClient = new HttpClient();

        $httpClient->getEmitter()->attach(
            new HttpMockSubscriber(
                $responseFixtures
            )
        );

        return $httpClient;
    }

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
