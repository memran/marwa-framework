<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

use Marwa\Framework\Application;
use Marwa\Framework\Config\CacheConfig;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Supports\Config;
use MatthiasMullie\Scrapbook\Adapters\Apc;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Adapters\SQLite;
use MatthiasMullie\Scrapbook\Buffered\BufferedStore;
use MatthiasMullie\Scrapbook\Buffered\TransactionalStore;
use MatthiasMullie\Scrapbook\KeyValueStore;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use MatthiasMullie\Scrapbook\Scale\StampedeProtector;

final class ScrapbookCacheAdapter implements CacheInterface
{
    /**
     * @var array{
     *     enabled: bool,
     *     driver: string,
     *     namespace: string,
     *     buffered: bool,
     *     transactional: bool,
     *     stampede: array{enabled: bool, sla: int},
     *     sqlite: array{path: string, table: string},
     *     memory: array{limit: int|string|null}
     * }|null
     */
    private ?array $cacheConfig = null;

    private ?KeyValueStore $store = null;
    private ?SimpleCache $cache = null;
    private ?TransactionalStore $transactions = null;

    public function __construct(
        private Application $app,
        private Config $config
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->simpleCache()->get($key, $default);
    }

    public function put(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->simpleCache()->set($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->simpleCache()->has($key);
    }

    public function forget(string $key): bool
    {
        return $this->simpleCache()->delete($key);
    }

    public function flush(): bool
    {
        return $this->simpleCache()->clear();
    }

    public function remember(string $key, null|int|\DateInterval $ttl, \Closure $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, null);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function putMany(array $values, null|int|\DateInterval $ttl = null): bool
    {
        return $this->simpleCache()->setMultiple($values, $ttl);
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function many(array $keys, mixed $default = null): array
    {
        $values = $this->simpleCache()->getMultiple($keys, $default);

        if (!is_array($values)) {
            return [];
        }

        /** @var array<string, mixed> $values */
        return $values;
    }

    public function increment(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        return $this->store()->increment($key, $offset, $initial, $ttl);
    }

    public function decrement(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        return $this->store()->decrement($key, $offset, $initial, $ttl);
    }

    public function begin(): void
    {
        $this->transactionalStore()->begin();
    }

    public function commit(): bool
    {
        return $this->transactionalStore()->commit();
    }

    public function rollback(): bool
    {
        return $this->transactionalStore()->rollback();
    }

    public function collection(string $name): self
    {
        $clone = new self($this->app, $this->config);
        $clone->cacheConfig = $this->configuration();
        $clone->cacheConfig['namespace'] = $this->collectionNamespace($name);

        return $clone;
    }

    public function psr16(): SimpleCache
    {
        return $this->simpleCache();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     namespace: string,
     *     buffered: bool,
     *     transactional: bool,
     *     stampede: array{enabled: bool, sla: int},
     *     sqlite: array{path: string, table: string},
     *     memory: array{limit: int|string|null}
     * }
     */
    public function configuration(): array
    {
        if ($this->cacheConfig !== null) {
            return $this->cacheConfig;
        }

        $this->config->loadIfExists(CacheConfig::KEY . '.php');
        $this->cacheConfig = CacheConfig::merge($this->app, $this->config->getArray(CacheConfig::KEY, []));

        return $this->cacheConfig;
    }

    private function simpleCache(): SimpleCache
    {
        if ($this->cache instanceof SimpleCache) {
            return $this->cache;
        }

        $this->cache = new SimpleCache($this->store());

        return $this->cache;
    }

    private function store(): KeyValueStore
    {
        if ($this->store instanceof KeyValueStore) {
            return $this->store;
        }

        $config = $this->configuration();

        if (!$config['enabled']) {
            $this->store = new MemoryStore();

            return $this->store;
        }

        $store = match ($config['driver']) {
            'apcu', 'apc' => $this->buildApcStore(),
            'memory', 'array' => new MemoryStore($this->resolveMemoryLimit($config['memory']['limit'])),
            'sqlite' => $this->buildSqliteStore($config['sqlite']['path'], $config['sqlite']['table']),
            default => $this->buildSqliteStore($config['sqlite']['path'], $config['sqlite']['table']),
        };

        if ($config['namespace'] !== '') {
            $store = $store->getCollection($config['namespace']);
        }

        if ($config['stampede']['enabled']) {
            $store = new StampedeProtector($store, $config['stampede']['sla']);
        }

        if ($config['buffered']) {
            $store = new BufferedStore($store);
        }

        if ($config['transactional']) {
            $this->transactions = new TransactionalStore($store);
            $store = $this->transactions;
        }

        $this->store = $store;

        return $this->store;
    }

    private function transactionalStore(): TransactionalStore
    {
        if ($this->transactions instanceof TransactionalStore) {
            return $this->transactions;
        }

        $this->transactions = new TransactionalStore($this->store());
        $this->store = $this->transactions;
        $this->cache = new SimpleCache($this->transactions);

        return $this->transactions;
    }

    private function buildApcStore(): KeyValueStore
    {
        if (!extension_loaded('apcu')) {
            return new MemoryStore($this->resolveMemoryLimit(null));
        }

        return new Apc();
    }

    private function buildSqliteStore(string $path, string $table): KeyValueStore
    {
        if (!extension_loaded('pdo_sqlite')) {
            return new MemoryStore($this->resolveMemoryLimit(null));
        }

        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create cache directory [%s].', $directory));
        }

        $pdo = new \PDO('sqlite:' . $path);

        return new SQLite($pdo, $table);
    }

    private function collectionNamespace(string $name): string
    {
        $base = $this->configuration()['namespace'];
        $suffix = trim($name);

        if ($suffix === '') {
            return $base;
        }

        if ($base === '') {
            return $suffix;
        }

        return $base . ':' . $suffix;
    }

    private function resolveMemoryLimit(int|string|null $limit): int
    {
        if (is_int($limit)) {
            return $limit;
        }

        if (is_string($limit) && $limit !== '') {
            return $this->shorthandToBytes($limit);
        }

        $configured = ini_get('memory_limit');

        if ($configured === '' || $configured === '-1') {
            return PHP_INT_MAX;
        }

        return (int) floor($this->shorthandToBytes($configured) / 10);
    }

    private function shorthandToBytes(string $value): int
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '' || $normalized === '-1') {
            return PHP_INT_MAX;
        }

        if (is_numeric($normalized)) {
            return (int) $normalized;
        }

        $unit = substr($normalized, -1);
        $amount = (int) substr($normalized, 0, -1);

        return match ($unit) {
            'G' => $amount * 1024 * 1024 * 1024,
            'M' => $amount * 1024 * 1024,
            'K' => $amount * 1024,
            default => (int) $normalized,
        };
    }
}
