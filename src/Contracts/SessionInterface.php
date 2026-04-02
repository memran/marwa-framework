<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface SessionInterface
{
    public function start(): void;

    public function isStarted(): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function flash(string $key, mixed $value): void;

    public function now(string $key, mixed $value): void;

    public function has(string $key): bool;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;

    public function forget(string $key): void;

    /**
     * @param list<string>|null $keys
     */
    public function keep(?array $keys = null): void;

    public function reflash(): void;

    public function flush(): void;

    public function regenerate(bool $destroy = false): void;

    public function invalidate(): void;

    public function id(): string;

    public function close(): void;
}
