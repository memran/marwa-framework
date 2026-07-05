<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\Cache\NatsBucket;
use Marwa\Framework\Adapters\Cache\NatsBucketEntry;
use Marwa\Framework\Adapters\Cache\NatsBucketInterface;
use Marwa\Framework\Adapters\Cache\NatsStore;
use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class NatsCacheSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-nats-cache-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_KEY=test-nats-cache-key\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->basePath . '/config/cache.php');
        @unlink($this->basePath . '/.env');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
        unset($GLOBALS['marwa_app'], $_ENV['APP_ENV'], $_ENV['APP_KEY'], $_ENV['TIMEZONE'], $_SERVER['APP_ENV'], $_SERVER['APP_KEY'], $_SERVER['TIMEZONE']);
    }

    public function testNatsStoreSupportsBasicCacheOperations(): void
    {
        $store = new NatsStore(new FakeNatsBucket(), signatureSecret: 'tests');

        self::assertTrue($store->set('name', 'Marwa', 60));
        self::assertSame('Marwa', $store->get('name', $token));
        self::assertIsInt($token);
        self::assertTrue($store->delete('name'));
        self::assertFalse($store->get('name'));
    }

    public function testNatsStoreSupportsMultiOperationsAndExpiration(): void
    {
        $store = new NatsStore(new FakeNatsBucket(), signatureSecret: 'tests');

        self::assertSame([
            'a' => true,
            'b' => true,
        ], $store->setMulti(['a' => 1, 'b' => 2], 60));
        self::assertSame(['a' => 1, 'b' => 2], $store->getMulti(['a', 'b', 'c'], $tokens));
        self::assertSame(['a', 'b'], array_keys($tokens ?? []));
        self::assertSame(['a' => true, 'b' => true], $store->deleteMulti(['a', 'b']));

        self::assertTrue($store->set('expired', 'value', -1));
        self::assertFalse($store->get('expired'));
    }

    public function testNatsStoreSupportsAddReplaceCasAndTouch(): void
    {
        $store = new NatsStore(new FakeNatsBucket(), signatureSecret: 'tests');

        self::assertTrue($store->add('draft', 'one', 60));
        self::assertFalse($store->add('draft', 'two', 60));
        self::assertTrue($store->replace('draft', 'two', 60));
        self::assertSame('two', $store->get('draft', $token));
        self::assertTrue($store->cas($token, 'draft', 'three', 60));
        self::assertSame('three', $store->get('draft'));
        self::assertFalse($store->cas($token, 'draft', 'stale', 60));
        self::assertTrue($store->touch('draft', 60));
        self::assertFalse($store->touch('missing', 60));
    }

    public function testNatsStoreDoesNotDeleteWithStaleCasTokenForExpiredWrite(): void
    {
        $store = new NatsStore(new FakeNatsBucket(), signatureSecret: 'tests');

        self::assertTrue($store->set('draft', 'one', 60));
        self::assertSame('one', $store->get('draft', $staleToken));
        self::assertTrue($store->cas($staleToken, 'draft', 'two', 60));
        self::assertFalse($store->cas($staleToken, 'draft', 'expired', -1));
        self::assertSame('two', $store->get('draft', $currentToken));

        self::assertTrue($store->cas($currentToken, 'draft', 'expired', -1));
        self::assertFalse($store->get('draft'));
    }

    public function testNatsStoreSupportsCountersCollectionsAndFlush(): void
    {
        $bucket = new FakeNatsBucket();
        $store = new NatsStore($bucket, signatureSecret: 'tests');
        $users = $store->getCollection('users');
        $posts = $store->getCollection('posts');

        self::assertSame(10, $store->increment('counter', 5, 10, 60));
        self::assertSame(15, $store->increment('counter', 5, 10, 60));
        self::assertSame(12, $store->decrement('counter', 3, 10, 60));
        self::assertTrue($store->set('text', 'not-numeric', 60));
        self::assertFalse($store->increment('text'));

        self::assertTrue($users->set('listing', ['alice'], 60));
        self::assertTrue($posts->set('listing', ['post-1'], 60));
        self::assertSame(['alice'], $users->get('listing'));
        self::assertSame(['post-1'], $posts->get('listing'));
        self::assertTrue($users->flush());
        self::assertFalse($users->get('listing'));
        self::assertSame(['post-1'], $posts->get('listing'));
        self::assertNotSame([], $bucket->all());
    }

    public function testNatsBucketNormalizesRealEntryLists(): void
    {
        $bucket = new NatsBucket(new FakeRawNatsBucket());

        self::assertSame([
            'users:listing' => 'payload',
            'legacy:key' => 'legacy-payload',
        ], $bucket->all());
    }

    public function testNatsStoreDoesNotRehydrateObjects(): void
    {
        $store = new NatsStore(new FakeNatsBucket(), signatureSecret: 'tests');

        NatsWakeupProbe::$woken = false;
        self::assertTrue($store->set('payload', new NatsWakeupProbe(), 60));

        $value = $store->get('payload');

        self::assertFalse(NatsWakeupProbe::$woken);
        self::assertNotInstanceOf(NatsWakeupProbe::class, $value);
    }

    public function testNatsStoreRequiresSigningSecret(): void
    {
        $store = new NatsStore(new FakeNatsBucket());

        unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('APP_KEY or an explicit cache signing secret');

        $store->set('name', 'Marwa', 60);
    }

    public function testNatsDriverRequiresOptionalClientPackage(): void
    {
        file_put_contents(
            $this->basePath . '/config/cache.php',
            <<<'PHP'
<?php

return [
    'driver' => 'nats',
];
PHP
        );

        $app = new Application($this->basePath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('composer require basis-company/nats');

        $app->cache()->put('name', 'Marwa', 60);
    }
}

final class FakeNatsBucket implements NatsBucketInterface
{
    /**
     * @var array<string, array{value: string, revision: int}>
     */
    private array $values = [];

    public function get(string $key): ?string
    {
        return $this->values[$key]['value'] ?? null;
    }

    public function getEntry(string $key): ?NatsBucketEntry
    {
        if (!isset($this->values[$key])) {
            return null;
        }

        return new NatsBucketEntry($this->values[$key]['value'], $this->values[$key]['revision']);
    }

    public function put(string $key, string $value): bool
    {
        $this->values[$key] = [
            'value' => $value,
            'revision' => ($this->values[$key]['revision'] ?? 0) + 1,
        ];

        return true;
    }

    public function create(string $key, string $value): bool
    {
        if (isset($this->values[$key])) {
            return false;
        }

        $this->values[$key] = [
            'value' => $value,
            'revision' => 1,
        ];

        return true;
    }

    public function update(string $key, string $value, int $revision): bool
    {
        if (!isset($this->values[$key]) || $this->values[$key]['revision'] !== $revision) {
            return false;
        }

        $this->values[$key] = [
            'value' => $value,
            'revision' => $revision + 1,
        ];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function purge(string $key): bool
    {
        return $this->delete($key);
    }

    public function all(): array
    {
        $values = [];

        foreach ($this->values as $key => $entry) {
            $values[$key] = $entry['value'];
        }

        return $values;
    }
}

final class FakeRawNatsBucket
{
    /**
     * @return array<int|string, object|string>
     */
    public function getAll(): array
    {
        return [
            (object) [
                'key' => 'users:listing',
                'value' => 'payload',
                'revision' => 1,
            ],
            'legacy:key' => 'legacy-payload',
            (object) [
                'key' => '',
                'value' => 'ignored',
                'revision' => 2,
            ],
        ];
    }
}

final class NatsWakeupProbe
{
    public static bool $woken = false;

    public function __wakeup(): void
    {
        self::$woken = true;
    }
}
