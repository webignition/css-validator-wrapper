<?php

namespace webignition\Tests\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use phpmock\mockery\PHPMockery;

abstract class AbstractBaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $this->httpClient = new HttpClient(['handler' => HandlerStack::create($this->mockHandler)]);

        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'microtime'
        )->andReturn(1);
    }

    /**
     * @param array $httpFixtures
     */
    protected function appendHttpFixtures(array $httpFixtures)
    {
        foreach ($httpFixtures as $httpFixture) {
            $this->mockHandler->append($httpFixture);
        }
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
     * {@inheritdoc}
     */
    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->mockHandler->count());
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
