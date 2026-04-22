<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

use MatthiasMullie\Scrapbook\KeyValueStore;

final class FileStore implements KeyValueStore
{
    private const EXPIRE_RELATIVE_THRESHOLD = 2592000;

    public function __construct(
        private string $root,
        private string $namespace = ''
    ) {
        $this->root = rtrim($this->root, DIRECTORY_SEPARATOR);
        $this->namespace = trim($this->namespace);
    }

    public function get(string $key, mixed &$token = null): mixed
    {
        $payload = $this->readPayload($key);

        if ($payload === null) {
            return false;
        }

        $token = $payload['version'];

        return $payload['value'];
    }

    /**
     * @param list<string> $keys
     * @param-out array<string, int>|null $tokens
     */
    public function getMulti(array $keys, ?array &$tokens = null): array
    {
        $values = [];
        $tokens = null;

        foreach ($keys as $key) {
            $payload = $this->readPayload((string) $key);

            if ($payload === null) {
                continue;
            }

            $values[$key] = $payload['value'];
            $tokens ??= [];
            $tokens[$key] = $payload['version'];
        }

        return $values;
    }

    public function set(string $key, mixed $value, int $expire = 0): bool
    {
        return $this->writePayload($key, $value, $expire);
    }

    public function setMulti(array $items, int $expire = 0): array
    {
        $results = [];

        foreach ($items as $key => $value) {
            $results[$key] = $this->set((string) $key, $value, $expire);
        }

        return $results;
    }

    public function delete(string $key): bool
    {
        $path = $this->pathForKey($key);

        if (!is_file($path)) {
            return true;
        }

        return @unlink($path);
    }

    public function deleteMulti(array $keys): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->delete((string) $key);
        }

        return $results;
    }

    public function add(string $key, mixed $value, int $expire = 0): bool
    {
        if ($this->readPayload($key) !== null) {
            return false;
        }

        return $this->writePayload($key, $value, $expire, 1);
    }

    public function replace(string $key, mixed $value, int $expire = 0): bool
    {
        $payload = $this->readPayload($key);

        if ($payload === null) {
            return false;
        }

        return $this->writePayload($key, $value, $expire, $payload['version'] + 1);
    }

    public function cas(mixed $token, string $key, mixed $value, int $expire = 0): bool
    {
        $payload = $this->readPayload($key);

        if ($payload === null || $payload['version'] !== $token) {
            return false;
        }

        return $this->writePayload($key, $value, $expire, $payload['version'] + 1);
    }

    public function increment(string $key, int $offset = 1, int $initial = 0, int $expire = 0): int|false
    {
        return $this->changeNumericValue($key, $offset, $initial, $expire, true);
    }

    public function decrement(string $key, int $offset = 1, int $initial = 0, int $expire = 0): int|false
    {
        return $this->changeNumericValue($key, $offset, $initial, $expire, false);
    }

    public function touch(string $key, int $expire): bool
    {
        $payload = $this->readPayload($key);

        if ($payload === null) {
            return false;
        }

        return $this->writePayload($key, $payload['value'], $expire, $payload['version'] + 1);
    }

    public function flush(): bool
    {
        $directory = $this->collectionRoot();

        if (!is_dir($directory)) {
            return true;
        }

        $this->deleteDirectoryContents($directory);

        return true;
    }

    public function getCollection(string $name): KeyValueStore
    {
        $collection = trim($name);
        $namespace = $this->namespace === ''
            ? $collection
            : $this->namespace . ':' . $collection;

        return new self($this->root, $namespace);
    }

    /**
     * @return array{value: mixed, expiresAt: int, version: int}|null
     */
    private function readPayload(string $key): ?array
    {
        $path = $this->pathForKey($key);

        if (!is_file($path)) {
            return null;
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return null;
            }

            $contents = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if ($contents === false || $contents === '') {
            @unlink($path);

            return null;
        }

        try {
            $payload = unserialize($contents, ['allowed_classes' => true]);
        } catch (\Throwable) {
            $payload = false;
        }

        if (!is_array($payload) || !array_key_exists('value', $payload) || !array_key_exists('expiresAt', $payload) || !array_key_exists('version', $payload)) {
            @unlink($path);

            return null;
        }

        $expiresAt = (int) $payload['expiresAt'];

        if ($expiresAt > 0 && $expiresAt <= time()) {
            @unlink($path);

            return null;
        }

        return [
            'value' => $payload['value'],
            'expiresAt' => $expiresAt,
            'version' => (int) $payload['version'],
        ];
    }

    private function writePayload(string $key, mixed $value, int $expire, int $version = 0): bool
    {
        $path = $this->pathForKey($key);
        $this->ensureDirectory(dirname($path));

        $handle = @fopen($path, 'c+b');

        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $currentVersion = $version;

            if ($version === 0) {
                $existing = $this->readLockedPayload($handle);
                $currentVersion = is_array($existing) ? ((int) $existing['version']) + 1 : 1;
            }

            $payload = serialize([
                'value' => $value,
                'expiresAt' => $this->normalizeExpire($expire),
                'version' => $currentVersion,
            ]);

            ftruncate($handle, 0);
            rewind($handle);
            $written = fwrite($handle, $payload);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return $written !== false;
    }

    /**
     * @return array{value: mixed, expiresAt: int, version: int}|null
     */
    /**
     * @param resource $handle
     * @return array{value: mixed, expiresAt: int, version: int}|null
     */
    private function readLockedPayload($handle): ?array
    {
        rewind($handle);
        $contents = stream_get_contents($handle);

        if ($contents === false || $contents === '') {
            return null;
        }

        try {
            $payload = unserialize($contents, ['allowed_classes' => true]);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($payload) || !array_key_exists('value', $payload) || !array_key_exists('expiresAt', $payload) || !array_key_exists('version', $payload)) {
            return null;
        }

        return [
            'value' => $payload['value'],
            'expiresAt' => (int) $payload['expiresAt'],
            'version' => (int) $payload['version'],
        ];
    }

    private function changeNumericValue(string $key, int $offset, int $initial, int $expire, bool $increment): int|false
    {
        $path = $this->pathForKey($key);
        $this->ensureDirectory(dirname($path));

        $handle = @fopen($path, 'c+b');

        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $payload = $this->readLockedPayload($handle);
            $current = $initial;

            if ($payload !== null) {
                if (!is_numeric($payload['value'])) {
                    return false;
                }

                $current = (int) $payload['value'];
            }

            $current = $increment ? $current + $offset : $current - $offset;

            $serialized = serialize([
                'value' => $current,
                'expiresAt' => $this->normalizeExpire($expire),
                'version' => $payload === null ? 1 : $payload['version'] + 1,
            ]);

            ftruncate($handle, 0);
            rewind($handle);
            $written = fwrite($handle, $serialized);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return $written !== false ? $current : false;
    }

    private function pathForKey(string $key): string
    {
        $hash = hash('sha256', $this->namespace . '|' . $key);

        return $this->collectionRoot() . DIRECTORY_SEPARATOR . substr($hash, 0, 2) . DIRECTORY_SEPARATOR . $hash . '.cache';
    }

    private function collectionRoot(): string
    {
        $namespace = $this->namespace === '' ? '_root' : hash('sha256', $this->namespace);

        return $this->root . DIRECTORY_SEPARATOR . $namespace;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create cache directory [%s].', $directory));
        }
    }

    private function normalizeExpire(int $expire): int
    {
        if ($expire < 0) {
            return time() - 1;
        }

        if ($expire === 0) {
            return 0;
        }

        if ($expire < self::EXPIRE_RELATIVE_THRESHOLD) {
            return time() + $expire;
        }

        return $expire;
    }

    private function deleteDirectoryContents(string $directory): void
    {
        $entries = glob($directory . DIRECTORY_SEPARATOR . '*');

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if (is_dir($entry)) {
                $this->deleteDirectoryContents($entry);
                @rmdir($entry);

                continue;
            }

            @unlink($entry);
        }

        @rmdir($directory);
    }
}
