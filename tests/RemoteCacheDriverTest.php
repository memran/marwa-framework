<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use FilesystemIterator;
use Marwa\Framework\Application;
use Marwa\Framework\Config\CacheConfig;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class RemoteCacheDriverTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-remote-cache-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_KEY=test-remote-cache-key\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_KEY'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testCacheConfigDefaultsExposeRedisAndMemcachedSections(): void
    {
        $defaults = CacheConfig::defaults(new Application($this->basePath));

        self::assertSame('127.0.0.1', $defaults['redis']['host']);
        self::assertSame(6379, $defaults['redis']['port']);
        self::assertSame(0, $defaults['redis']['database']);
        self::assertNull($defaults['redis']['password']);
        self::assertSame(1.0, $defaults['redis']['timeout']);
        self::assertSame([
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 1],
        ], $defaults['memcached']['servers']);
        self::assertNull($defaults['memcached']['persistent_id']);
    }

    public function testCacheDriverCanBeSelectedFromEnvironment(): void
    {
        $_ENV['CACHE_DRIVER'] = 'memory';
        $_SERVER['CACHE_DRIVER'] = 'memory';

        try {
            $defaults = CacheConfig::defaults(new Application($this->basePath));

            self::assertSame('memory', $defaults['driver']);
        } finally {
            unset($_ENV['CACHE_DRIVER'], $_SERVER['CACHE_DRIVER']);
        }
    }

    public function testCacheConfigNormalizesRedisAndMemcachedOverrides(): void
    {
        $merged = CacheConfig::merge(new Application($this->basePath), [
            'driver' => 'VALKEY',
            'redis' => [
                'host' => 'redis.internal',
                'port' => '6380',
                'database' => '-3',
                'password' => '',
                'timeout' => '0.25',
            ],
            'memcached' => [
                'servers' => [
                    ['host' => 'cache-a', 'port' => '11212', 'weight' => '4'],
                    ['host' => '', 'port' => 11211],
                    'invalid',
                ],
                'persistent_id' => '',
            ],
        ]);

        self::assertSame('valkey', $merged['driver']);
        self::assertSame('redis.internal', $merged['redis']['host']);
        self::assertSame(6380, $merged['redis']['port']);
        self::assertSame(0, $merged['redis']['database']);
        self::assertNull($merged['redis']['password']);
        self::assertSame(0.25, $merged['redis']['timeout']);
        self::assertSame([
            ['host' => 'cache-a', 'port' => 11212, 'weight' => 4],
        ], $merged['memcached']['servers']);
        self::assertNull($merged['memcached']['persistent_id']);
    }

    public function testEmptyMemcachedServerListFallsBackToDefaults(): void
    {
        $defaults = CacheConfig::defaults(new Application($this->basePath));
        $merged = CacheConfig::merge(new Application($this->basePath), [
            'memcached' => [
                'servers' => [],
            ],
        ]);

        self::assertSame($defaults['memcached']['servers'], $merged['memcached']['servers']);
    }

    public function testUnsupportedCacheDriverIsRejectedInsteadOfFallingBackToFiles(): void
    {
        $this->writeCacheConfig(['driver' => 'bogus']);

        $app = new Application($this->basePath);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unsupported cache driver [bogus]. Supported drivers: apc, apcu, array, file, memcached, memory, nats, redis, sqlite, valkey.'
        );

        $app->cache()->get('key');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function redisDrivers(): iterable
    {
        yield 'redis' => ['redis', 'redis'];
        yield 'valkey' => ['valkey', 'valkey'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('redisDrivers')]
    public function testRedisAndValkeyDriversRequirePhpRedisExtension(string $driver, string $expectedName): void
    {
        if (extension_loaded('redis')) {
            self::markTestSkipped('ext-redis is installed; the missing-extension path cannot be exercised.');
        }

        $this->writeCacheConfig(['driver' => $driver]);

        $app = new Application($this->basePath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The [%s] cache driver requires the phpredis extension (ext-redis).',
            $expectedName
        ));

        $app->cache()->put('key', 'value', 60);
    }

    public function testMemcachedDriverRequiresMemcachedExtension(): void
    {
        if (extension_loaded('memcached')) {
            self::markTestSkipped('ext-memcached is installed; the missing-extension path cannot be exercised.');
        }

        $this->writeCacheConfig(['driver' => 'memcached']);

        $app = new Application($this->basePath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The [memcached] cache driver requires the memcached extension (ext-memcached).');

        $app->cache()->put('key', 'value', 60);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function writeCacheConfig(array $config): void
    {
        file_put_contents(
            $this->basePath . '/config/cache.php',
            "<?php\n\nreturn " . var_export($config, true) . ";\n"
        );
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
