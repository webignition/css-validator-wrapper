<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\CssValidatorWrapper\Tests\Wrapper;

use phpmock\mockery\PHPMockery;
use webignition\CssValidatorWrapper\ResourceStorage;

class ResourceStorageTest extends \PHPUnit\Framework\TestCase
{
    const MICROTIME = 123;

    /**
     * @dataProvider storeDataProvider
     */
    public function testStore(string $content, string $type, string $filenameHash, string $expectedPath)
    {
        if (file_exists($expectedPath)) {
            unlink($expectedPath);
        }

        $this->assertFalse(file_exists($expectedPath));

        $this->mockMicrotime(self::MICROTIME);
        $this->mockMd5($content . self::MICROTIME, $filenameHash);

        $resourceStorage = new ResourceStorage();

        $path = $resourceStorage->store($content, $type);

        $this->assertStoredFile($expectedPath, $path, $content);

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

    public function testDuplicate()
    {
        $content = '<!doctype html><html></html>';
        $type = 'html';
        $filenameHash = 'file-hash';
        $expectedPath = '/tmp/file-hash.html';

        $localPath = sys_get_temp_dir() . '/' . md5(microtime(true));

        file_put_contents($localPath, $content);

        $this->assertTrue(file_exists($localPath));

        $this->mockMicrotime(self::MICROTIME);
        $this->mockMd5($localPath . self::MICROTIME, $filenameHash);

        $resourceStorage = new ResourceStorage();

        $path = $resourceStorage->duplicate($localPath, $type);

        $this->assertStoredFile($expectedPath, $path, $content);

        $resourceStorage->deleteAll();

        $this->assertFalse(file_exists($expectedPath));
    }

    private function assertStoredFile(string $expectedPath, string $path, string $expectedContent)
    {
        $this->assertSame($expectedPath, $path);
        $this->assertTrue(file_exists($expectedPath));
        $this->assertEquals($expectedContent, file_get_contents($path));
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
