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
}
