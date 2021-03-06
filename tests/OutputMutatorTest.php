<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\CssValidatorWrapper\Tests;

use webignition\CssValidatorOutput\Model\ErrorMessage;
use webignition\CssValidatorOutput\Model\InfoMessage;
use webignition\CssValidatorOutput\Model\MessageList;
use webignition\CssValidatorOutput\Model\ObservationResponse;
use webignition\CssValidatorOutput\Model\Options;
use webignition\CssValidatorOutput\Model\ValidationOutput;
use webignition\CssValidatorOutput\Model\WarningMessage;
use webignition\CssValidatorWrapper\OutputMutator;
use webignition\UrlSourceMap\Source;
use webignition\UrlSourceMap\SourceMap;

class OutputMutatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OutputMutator
     */
    private $outputMutator;

    protected function setUp()
    {
        parent::setUp();

        $this->outputMutator = new OutputMutator();
    }

    public function testSetObservationResponseRef()
    {
        $originalRef = 'original-ref';
        $updatedRef = 'updated-ref';

        $options = new Options(true, 'ucn', 'en', 0, 'all', 'css3');

        $output = new ValidationOutput(
            $options,
            new ObservationResponse($originalRef, new \DateTime(), new MessageList())
        );

        $observationResponse = $output->getObservationResponse();
        $this->assertEquals($originalRef, $observationResponse->getRef());
        $this->assertNotEquals($updatedRef, $observationResponse->getRef());

        $updatedOutput = $this->outputMutator->setObservationResponseRef($output, $updatedRef);
        $updatedObservationResponse = $updatedOutput->getObservationResponse();

        $this->assertNotSame($output, $updatedOutput);
        $this->assertNotSame($observationResponse, $updatedObservationResponse);

        $this->assertEquals($originalRef, $observationResponse->getRef());
        $this->assertEquals($updatedRef, $updatedObservationResponse->getRef());
    }

    /**
     * @dataProvider setMessagesRefFromSourceMapDataProvider
     */
    public function testSetMessagesRefFromSourceMap(
        ValidationOutput $output,
        SourceMap $linkedResourcesMap,
        array $expectedMessages
    ) {
        $updatedOutput = $this->outputMutator->setMessagesRefFromSourceMap($output, $linkedResourcesMap);

        $this->assertNotSame($output, $updatedOutput);
        $this->assertEquals($expectedMessages, array_values($updatedOutput->getMessages()->getMessages()));
    }

    public function setMessagesRefFromSourceMapDataProvider(): array
    {
        return [
            'no messages' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([]))
                ),
                'sourceMap' => new SourceMap(),
                'expectedMessages' => [],
            ],
            'info message only' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([
                        new InfoMessage('info title', 'info description'),
                    ]))
                ),
                'sourceMap' => new SourceMap(),
                'expectedMessages' => [
                    new InfoMessage('info title', 'info description'),
                ],
            ],
            'error and warning, no matching resource urls' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([
                        new WarningMessage('warning title', 0, 'warning context', '/tmp/warning.html', 0),
                        new ErrorMessage('error title', 0, 'error context', '/tmp/error.html'),
                    ]))
                ),
                'sourceMap' => new SourceMap(),
                'expectedMessages' => [
                    new WarningMessage('warning title', 0, 'warning context', '/tmp/warning.html', 0),
                    new ErrorMessage('error title', 0, 'error context', '/tmp/error.html'),
                ],
            ],
            'error and warning, has matching resource urls' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([
                        new WarningMessage('warning title', 0, 'warning context', '/tmp/warning.html', 0),
                        new ErrorMessage('error title', 0, 'error context', '/tmp/error.html'),
                    ]))
                ),
                'sourceMap' => new SourceMap([
                    new Source('http://example.com/warning.css', '/tmp/warning.html'),
                    new Source('http://example.com/error.css', '/tmp/error.html'),
                ]),
                'expectedMessages' => [
                    new WarningMessage('warning title', 0, 'warning context', 'http://example.com/warning.css', 0),
                    new ErrorMessage('error title', 0, 'error context', 'http://example.com/error.css'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider setMessagesRefFromUrlDataProvider
     */
    public function testSetMessagesRefFromUrl(
        ValidationOutput $output,
        string $url,
        array $expectedMessages
    ) {
        $updatedOutput = $this->outputMutator->setMessagesRefFromUrl($output, $url);

        $this->assertNotSame($output, $updatedOutput);
        $this->assertEquals($expectedMessages, array_values($updatedOutput->getMessages()->getMessages()));
    }

    public function setMessagesRefFromUrlDataProvider(): array
    {
        return [
            'no messages, empty url' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([]))
                ),
                'url' => '',
                'expectedMessages' => [],
            ],
            'info message only, empty url' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([
                        new InfoMessage('info title', 'info description'),
                    ]))
                ),
                'url' => '',
                'expectedMessages' => [
                    new InfoMessage('info title', 'info description'),
                ],
            ],
            'error and warning, empty url' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([
                        new WarningMessage('warning title', 0, 'warning context', '/tmp/warning.html', 0),
                        new ErrorMessage('error title', 0, 'error context', '/tmp/error.html'),
                    ]))
                ),
                'url' => '',
                'expectedMessages' => [
                    new WarningMessage('warning title', 0, 'warning context', '', 0),
                    new ErrorMessage('error title', 0, 'error context', ''),
                ],
            ],
            'error and warning, has url' => [
                'output' => new ValidationOutput(
                    $this->createCssValidationOutputOptions(),
                    new ObservationResponse('http://example.com/', new \DateTime(), $this->createMessageList([
                        new WarningMessage('warning title', 0, 'warning context', '/tmp/warning.html', 0),
                        new ErrorMessage('error title', 0, 'error context', '/tmp/error.html'),
                    ]))
                ),
                'url' => 'file:/tmp/source.css',
                'expectedMessages' => [
                    new WarningMessage('warning title', 0, 'warning context', 'file:/tmp/source.css', 0),
                    new ErrorMessage('error title', 0, 'error context', 'file:/tmp/source.css'),
                ],
            ],
        ];
    }

    private function createMessageList(array $messages): MessageList
    {
        $messageList = new MessageList();

        foreach ($messages as $message) {
            $messageList->addMessage($message);
        }

        return $messageList;
    }

    private function createCssValidationOutputOptions(): Options
    {
        return new Options(true, 'ucn', 'en', 0, 'all', 'css3');
    }
}
