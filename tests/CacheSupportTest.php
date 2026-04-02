<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\Cache\ScrapbookCacheAdapter;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\CacheInterface;
use PHPUnit\Framework\TestCase;

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
}
