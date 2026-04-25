<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use FilesystemIterator;
use Marwa\Framework\Adapters\Cache\ScrapbookCacheAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Supports\Config;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CacheSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-cache-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
        file_put_contents(
            $this->basePath . '/config/cache.php',
            <<<'PHP'
<?php

return [
    'driver' => 'memory',
    'namespace' => 'framework-tests',
    'buffered' => true,
    'transactional' => false,
    'stampede' => [
        'enabled' => false,
        'sla' => 250,
    ],
];
PHP
        );
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/config/cache.php');
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['TIMEZONE']);
    }

    public function testCacheDefaultDriverPersistsValuesOnDisk(): void
    {
        $basePath = $this->createBasePathWithoutCacheConfig();
        try {
            $app = new Application($basePath);

            $cache = $app->cache();

            self::assertTrue($cache->put('disk-key', 'disk-value', 60));
            self::assertSame('disk-value', $cache->get('disk-key'));

            $cacheFiles = $this->collectFiles($basePath . '/storage/cache/framework');

            self::assertNotEmpty($cacheFiles);

            $app = new Application($basePath);
            self::assertSame('disk-value', $app->cache()->get('disk-key'));
        } finally {
            $this->removeDirectory($basePath);
        }
    }

    public function testCacheBindingAndHelperResolveTheScrapbookAdapter(): void
    {
        $app = new Application($this->basePath);

        self::assertInstanceOf(CacheInterface::class, $app->cache());
        self::assertInstanceOf(ScrapbookCacheAdapter::class, cache());
    }

    public function testCacheSupportsBasicRememberAndMultiOperations(): void
    {
        $app = new Application($this->basePath);
        $cache = $app->cache();

        self::assertTrue($cache->put('name', 'Marwa', 60));
        self::assertSame('Marwa', $cache->get('name'));
        self::assertTrue($cache->has('name'));
        self::assertTrue($cache->putMany([
            'a' => 1,
            'b' => 2,
        ], 60));
        self::assertSame([
            'a' => 1,
            'b' => 2,
            'c' => null,
        ], $cache->many(['a', 'b', 'c']));

        $value = $cache->remember('computed', 60, static fn (): string => 'cached');

        self::assertSame('cached', $value);
        self::assertSame('cached', cache('computed'));
        self::assertTrue($cache->put('counter', 0, 60));
        self::assertSame(5, $cache->increment('counter', 5, 0, 60));
        self::assertSame(3, $cache->decrement('counter', 2, 0, 60));
    }

    public function testCacheCollectionsAndTransactionsAreIsolated(): void
    {
        $app = new Application($this->basePath);
        /** @var ScrapbookCacheAdapter $cache */
        $cache = $app->cache();

        $users = $cache->collection('users');
        $posts = $cache->collection('posts');

        $users->put('listing', ['alice'], 60);
        $posts->put('listing', ['post-1'], 60);

        self::assertSame(['alice'], $users->get('listing'));
        self::assertSame(['post-1'], $posts->get('listing'));

        $cache->begin();
        $cache->put('draft', 'pending', 60);
        self::assertSame('pending', $cache->get('draft'));
        self::assertTrue($cache->rollback());
        self::assertNull($cache->get('draft'));

        $cache->begin();
        $cache->put('draft', 'committed', 60);
        self::assertTrue($cache->commit());
        self::assertSame('committed', $cache->get('draft'));
    }

    public function testSharedCacheReadsUpdatedGlobalConfig(): void
    {
        $app = new Application($this->basePath);
        /** @var ScrapbookCacheAdapter $cache */
        $cache = $app->cache();
        $cache->put('runtime-only', 'memory-value', 60);

        /** @var Config $config */
        $config = $app->make(Config::class);
        $config->set('cache.driver', 'file');
        $config->set('cache.file.path', $this->basePath . '/storage/cache/framework');
        $config->set('cache.namespace', 'framework-tests');

        self::assertTrue($cache->put('disk-key', 'disk-value', 60));
        self::assertSame('disk-value', $cache->get('disk-key'));

        $updatedConfig = sprintf(
            <<<'PHP'
<?php

return [
    'driver' => 'file',
    'namespace' => 'framework-tests',
    'buffered' => true,
    'transactional' => false,
    'stampede' => [
        'enabled' => false,
        'sla' => 250,
    ],
    'file' => [
        'path' => '%s',
    ],
];
PHP,
            $this->basePath . '/storage/cache/framework'
        );
        file_put_contents($this->basePath . '/config/cache.php', $updatedConfig);

        $app = new Application($this->basePath);
        self::assertSame('disk-value', $app->cache()->get('disk-key'));
        self::assertNull($app->cache()->get('runtime-only'));
    }

    private function createBasePathWithoutCacheConfig(): string
    {
        $basePath = sys_get_temp_dir() . '/marwa-cache-file-' . bin2hex(random_bytes(6));
        mkdir($basePath, 0777, true);
        mkdir($basePath . '/config', 0777, true);
        file_put_contents($basePath . '/.env', "APP_ENV=testing\nTIMEZONE=UTC\n");

        return $basePath;
    }

    /**
     * @return list<string>
     */
    private function collectFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $files[] = $item->getPathname();
            }
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());

                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
