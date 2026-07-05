<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

interface NatsBucketInterface
{
    public function get(string $key): ?string;

    public function getEntry(string $key): ?NatsBucketEntry;

    public function put(string $key, string $value): bool;

    public function create(string $key, string $value): bool;

    public function update(string $key, string $value, int $revision): bool;

    public function delete(string $key): bool;

    public function purge(string $key): bool;

    /**
     * @return array<string, string>
     */
    public function all(): array;
}
