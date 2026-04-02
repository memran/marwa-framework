<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

use Marwa\Framework\Contracts\CacheInterface;
use Psr\SimpleCache\CacheInterface as Psr16;

/**
 * PSR-16 cache adapter with Laravel-style convenience methods.
 */
final class SymfonyCacheAdapter implements CacheInterface
{
    public function __construct(private Psr16 $cache) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function put(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function forget(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function flush(): bool
    {
        return $this->cache->clear();
    }

    public function remember(string $key, null|int|\DateInterval $ttl, \Closure $callback): mixed
    {
        $value = $this->cache->get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        $this->cache->set($key, $value, $ttl);
        return $value;
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->cache->set($key, $value, null);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function putMany(array $values, null|int|\DateInterval $ttl = null): bool
    {
        return $this->cache->setMultiple($values, $ttl);
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function many(array $keys, mixed $default = null): array
    {
        $values = $this->cache->getMultiple($keys, $default);

        if (!is_array($values)) {
            return [];
        }

        /** @var array<string, mixed> $values */
        return $values;
    }

    public function increment(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        $current = $this->cache->get($key, null);

        if ($current === null) {
            $this->cache->set($key, $initial, $ttl === 0 ? null : $ttl);

            return $initial;
        }

        if (!is_numeric($current)) {
            return false;
        }

        $next = (int) $current + $offset;
        $this->cache->set($key, $next, $ttl === 0 ? null : $ttl);

        return $next;
    }

    public function decrement(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        $current = $this->cache->get($key, null);

        if ($current === null) {
            $this->cache->set($key, $initial, $ttl === 0 ? null : $ttl);

            return $initial;
        }

        if (!is_numeric($current)) {
            return false;
        }

        $next = max(0, (int) $current - $offset);
        $this->cache->set($key, $next, $ttl === 0 ? null : $ttl);

        return $next;
    }
}
