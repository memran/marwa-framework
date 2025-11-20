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
}
