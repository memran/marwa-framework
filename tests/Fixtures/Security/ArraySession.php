<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Security;

use Marwa\Framework\Contracts\SessionInterface;

final class ArraySession implements SessionInterface
{
    private bool $started = false;
    private string $id;

    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(16));
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function flash(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function now(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function all(): array
    {
        return $this->values;
    }

    public function forget(string $key): void
    {
        unset($this->values[$key]);
    }

    public function keep(?array $keys = null): void {}

    public function reflash(): void {}

    public function flush(): void
    {
        $this->values = [];
    }

    public function regenerate(bool $destroy = false): void
    {
        $this->id = bin2hex(random_bytes(16));
    }

    public function invalidate(): void
    {
        $this->flush();
        $this->started = false;
        $this->id = bin2hex(random_bytes(16));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function close(): void
    {
        $this->started = false;
    }
}
