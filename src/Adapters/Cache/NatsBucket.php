<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

final class NatsBucket implements NatsBucketInterface
{
    public function __construct(private object $bucket) {}

    public function get(string $key): ?string
    {
        try {
            $value = $this->bucket->get($key);
        } catch (\Throwable) {
            return null;
        }

        return is_string($value) ? $value : null;
    }

    public function getEntry(string $key): ?NatsBucketEntry
    {
        try {
            $entry = $this->bucket->getEntry($key);
        } catch (\Throwable) {
            $value = $this->get($key);

            return $value !== null ? new NatsBucketEntry($value, 0) : null;
        }

        if (!is_object($entry)) {
            return null;
        }

        $value = $entry->value ?? null;
        $revision = $entry->revision ?? null;

        if (!is_string($value)) {
            return null;
        }

        return new NatsBucketEntry($value, is_int($revision) ? $revision : (int) $revision);
    }

    public function put(string $key, string $value): bool
    {
        try {
            $this->bucket->put($key, $value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function create(string $key, string $value): bool
    {
        try {
            $this->bucket->update($key, $value, 0);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function update(string $key, string $value, int $revision): bool
    {
        try {
            $this->bucket->update($key, $value, $revision);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $this->bucket->delete($key);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function purge(string $key): bool
    {
        try {
            $this->bucket->purge($key);

            return true;
        } catch (\Throwable) {
            return $this->delete($key);
        }
    }

    public function all(): array
    {
        try {
            $values = $this->bucket->getAll();
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($values)) {
            return [];
        }

        $entries = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $entries[$key] = $value;

                continue;
            }

            if (!is_object($value)) {
                continue;
            }

            $entryKey = $value->key ?? null;
            $entryValue = $value->value ?? null;

            if (is_string($entryKey) && $entryKey !== '' && is_string($entryValue)) {
                $entries[$entryKey] = $entryValue;
            }
        }

        return $entries;
    }
}
