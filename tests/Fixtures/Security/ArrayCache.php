<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Security;

use Marwa\Framework\Contracts\CacheInterface;

final class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function put(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function forget(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function flush(): bool
    {
        $this->values = [];

        return true;
    }

    public function remember(string $key, null|int|\DateInterval $ttl, \Closure $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $this->values[$key] = $callback();

        return $this->values[$key];
    }

    public function forever(string $key, mixed $value): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function putMany(array $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->values[$key] = $value;
        }

        return true;
    }

    public function many(array $keys, mixed $default = null): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->values[$key] ?? $default;
        }

        return $values;
    }

    public function increment(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        $value = $this->values[$key] ?? $initial;

        if (!is_int($value) && !is_numeric($value)) {
            return false;
        }

        $value = (int) $value + $offset;
        $this->values[$key] = $value;

        return $value;
    }

    public function decrement(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
    {
        $value = $this->values[$key] ?? $initial;

        if (!is_int($value) && !is_numeric($value)) {
            return false;
        }

        $value = (int) $value - $offset;
        $this->values[$key] = $value;

        return $value;
    }
}
