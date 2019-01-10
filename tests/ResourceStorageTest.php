<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use phpmock\mockery\PHPMockery;
use webignition\CssValidatorWrapper\ResourceStorage;

class ResourceStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider storeDataProvider
     */
    public function testStore(string $content, string $type, string $filenameHash, string $expectedPath)
    {
        if (file_exists($expectedPath)) {
            unlink($expectedPath);
        }

        $this->assertFalse(file_exists($expectedPath));

        $microtime = 123;

        $this->mockMicrotime($microtime);
        $this->mockMd5($content . $microtime, $filenameHash);

        $resourceStorage = new ResourceStorage();

        $path = $resourceStorage->store($content, $type);

        $this->assertTrue(file_exists($expectedPath));
        $this->assertEquals($content, file_get_contents($path));

        $resourceStorage->deleteAll();

        $this->assertFalse(file_exists($expectedPath));
    }

    public function storeDataProvider(): array
    {
        return [
            'html file' => [
                'content' => '<!doctype html><html></html>',
                'type' => 'html',
                'filenameHash' => 'file-hash-1',
                'expectedPath' => '/tmp/file-hash-1.html',
            ],
            'css file' => [
                'content' => 'html {}',
                'type' => 'css',
                'filenameHash' => 'file-hash-2',
                'expectedPath' => '/tmp/file-hash-2.css',
            ],
        ];
    }

    private function mockMicrotime(int $time)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'microtime'
        )->andReturn($time);
    }

    private function mockMd5(string $expectedInput, string $output)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'md5'
        )->withArgs(function (string $input) use ($expectedInput) {
            $this->assertSame($input, $expectedInput);

            return true;
        })->andReturn($output);
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
