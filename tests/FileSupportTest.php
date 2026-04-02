<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Supports\File;
use PHPUnit\Framework\TestCase;

final class FileSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-file-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);
    }

    public function testWriteReadAndMetadataWorkForTextFiles(): void
    {
        $path = $this->basePath . '/notes/example.txt';

        $file = File::path($path)->write('Hello Marwa');

        self::assertTrue($file->exists());
        self::assertSame($path, $file->pathName());
        self::assertSame('example.txt', $file->basename());
        self::assertSame('example', $file->name());
        self::assertSame('txt', $file->extension());
        self::assertSame('Hello Marwa', $file->read());
        self::assertGreaterThan(0, $file->size());
        self::assertNotNull($file->mime());
    }

    public function testJsonAppendAndPrependOperationsAreSupported(): void
    {
        $jsonPath = $this->basePath . '/config/app.json';
        File::path($jsonPath)->writeJson([
            'name' => 'Marwa',
            'debug' => true,
        ]);

        self::assertSame([
            'name' => 'Marwa',
            'debug' => true,
        ], File::path($jsonPath)->readJson());

        $textFile = File::path($this->basePath . '/logs/app.log')
            ->write("middle\n")
            ->prepend("start\n")
            ->append("end\n");

        self::assertSame("start\nmiddle\nend\n", $textFile->read());
    }

    public function testCopyMoveDeleteAndDirectoryCreationWork(): void
    {
        $source = File::path($this->basePath . '/source/report.txt')->write('report');
        $copied = $source->copyTo($this->basePath . '/copies/report.txt');

        self::assertSame('report', $copied->read());
        self::assertSame('report', $source->read());

        $moved = $copied->moveTo($this->basePath . '/archive/report.txt', overwrite: true);

        self::assertFalse(File::path($this->basePath . '/copies/report.txt')->exists());
        self::assertSame($this->basePath . '/archive/report.txt', $moved->pathName());
        self::assertTrue($moved->delete());
        self::assertTrue($moved->missing());

        $directory = File::path($this->basePath . '/exports/daily')->ensureDirectory();

        self::assertTrue(is_dir($directory->pathName()));
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
