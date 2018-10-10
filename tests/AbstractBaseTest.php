<?php

namespace webignition\Tests\CssValidatorWrapper;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use phpmock\mockery\PHPMockery;

abstract class AbstractBaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

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
            'webignition\WebResource',
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
