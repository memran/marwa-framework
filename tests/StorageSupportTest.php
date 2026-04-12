<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Supports\Storage;
use PHPUnit\Framework\TestCase;

final class StorageSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-storage-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'config', 0777, true);
        file_put_contents($this->basePath . DIRECTORY_SEPARATOR . '.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
    }

    public function testStorageWritesReadsAndListsFilesOnTheDefaultDisk(): void
    {
        $app = new Application($this->basePath);
        $storage = $app->storage();

        self::assertTrue($storage->write('docs/readme.txt', 'hello'));
        self::assertTrue($storage->writeJson('meta/app.json', ['name' => 'Marwa']));
        self::assertTrue($storage->makeDirectory('exports/daily'));

        self::assertTrue($storage->exists('docs/readme.txt'));
        self::assertSame('hello', $storage->read('docs/readme.txt'));
        self::assertSame(['name' => 'Marwa'], $storage->readJson('meta/app.json'));
        self::assertContains('docs/readme.txt', $storage->files('', true));
        self::assertContains('exports/daily', $storage->directories('', true));
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/app/docs/readme.txt'),
            str_replace('/', DIRECTORY_SEPARATOR, $storage->path('docs/readme.txt'))
        );
    }

    public function testStorageSupportsCopyMoveDeleteAndHelperAccess(): void
    {
        $app = new Application($this->basePath);
        $storage = storage();

        self::assertInstanceOf(Storage::class, $storage);
        self::assertTrue($storage->write('logs/app.log', 'line'));
        self::assertTrue($storage->copy('logs/app.log', 'archive/app.log'));
        self::assertTrue($storage->move('archive/app.log', 'archive/final.log'));
        self::assertTrue($storage->delete('logs/app.log'));

        self::assertFalse($storage->exists('logs/app.log'));
        self::assertTrue($storage->exists('archive/final.log'));
        self::assertGreaterThan(0, $storage->size('archive/final.log'));
        self::assertNotSame('', $storage->checksum('archive/final.log'));
    }

    public function testStorageCanSwitchToConfiguredPublicDisk(): void
    {
        $app = new Application($this->basePath);
        $public = $app->storage()->disk('public');

        self::assertTrue($public->write('avatars/user.txt', 'public'));
        self::assertSame('public', $public->read('avatars/user.txt'));
        self::assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/storage/app/public/avatars/user.txt'),
            str_replace('/', DIRECTORY_SEPARATOR, $public->path('avatars/user.txt'))
        );
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($current)) {
                $this->removeDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}
