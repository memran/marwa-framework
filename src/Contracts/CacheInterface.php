<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function put(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
    public function remember(string $key, null|int|\DateInterval $ttl, \Closure $callback): mixed;
    public function forever(string $key, mixed $value): bool;

    /**
     * @param array<string, mixed> $values
     */
    public function putMany(array $values, null|int|\DateInterval $ttl = null): bool;

    /**
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function many(array $keys, mixed $default = null): array;

    public function increment(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false;
    public function decrement(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false;
}
