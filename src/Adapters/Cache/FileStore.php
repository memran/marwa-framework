<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

use MatthiasMullie\Scrapbook\KeyValueStore;

final class FileStore implements KeyValueStore
{
    private const EXPIRE_RELATIVE_THRESHOLD = 2592000;
    private const PAYLOAD_VERSION = 2;

    public function __construct(
        private string $root,
        private string $namespace = '',
        private string $signatureSecret = ''
    ) {
        $this->root = rtrim($this->root, DIRECTORY_SEPARATOR);
        $this->namespace = trim($this->namespace);
        $this->signatureSecret = trim($this->signatureSecret);
    }

    public function get(string $key, mixed &$token = null): mixed
    {
        if (!$this->validateKeyOrFail($key)) {
            return false;
        }

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

    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Cache key cannot be empty.');
        }
    }

    public function validateKeyOrFail(string $key): bool
    {
        return $key !== '';
    }

    public function add(string $key, mixed $value, int $expire = 0): bool
    {
        $this->validateKey($key);

        return $this->writePayload($key, $value, $expire, 1);
    }

    public function replace(string $key, mixed $value, int $expire = 0): bool
    {
        $this->validateKey($key);

        $payload = $this->readPayload($key);

        if ($payload === null) {
            return false;
        }

        return $this->writePayload($key, $value, $expire, $payload['version'] + 1);
    }

    public function cas(mixed $token, string $key, mixed $value, int $expire = 0): bool
    {
        $this->validateKey($key);

        $payload = $this->readPayload($key);

        if ($payload === null || $payload['version'] !== $token) {
            return false;
        }

        return $this->writePayload($key, $value, $expire, $payload['version'] + 1);
    }

    public function increment(string $key, int $offset = 1, int $initial = 0, int $expire = 0): int|false
    {
        $this->validateKey($key);

        return $this->changeNumericValue($key, $offset, $initial, $expire, true);
    }

    public function decrement(string $key, int $offset = 1, int $initial = 0, int $expire = 0): int|false
    {
        $this->validateKey($key);

        return $this->changeNumericValue($key, $offset, $initial, $expire, false);
    }

    public function touch(string $key, int $expire): bool
    {
        $this->validateKey($key);

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

        try {
            $this->deleteDirectoryContents($directory);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function getCollection(string $name): KeyValueStore
    {
        $collection = trim($name);
        $namespace = $this->namespace === ''
            ? $collection
            : $this->namespace . ':' . $collection;

        return new self($this->root, $namespace, $this->signatureSecret);
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

        $payload = $this->decodePayload($contents);

        if ($payload === null) {
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

            $payload = $this->encodePayload([
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

        $payload = $this->decodePayload($contents);

        return $payload;
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

            $serialized = $this->encodePayload([
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

    /**
     * @param array{value: mixed, expiresAt: int, version: int} $payload
     */
    private function encodePayload(array $payload): string
    {
        $serialized = serialize($payload);

        return serialize([
            'version' => self::PAYLOAD_VERSION,
            'payload' => $serialized,
            'mac' => hash_hmac('sha256', $serialized, $this->signatureKey()),
        ]);
    }

    /**
     * @return array{value: mixed, expiresAt: int, version: int}|null
     */
    private function decodePayload(string $contents): ?array
    {
        try {
            $envelope = unserialize($contents, ['allowed_classes' => false]);
        } catch (\Throwable) {
            return null;
        }

        if (is_array($envelope) && $this->isSignedEnvelope($envelope)) {
            $serialized = $envelope['payload'];

            try {
                $payload = unserialize($serialized, ['allowed_classes' => false]);
            } catch (\Throwable) {
                return null;
            }

            return $this->normalizePayload($payload);
        }

        return null;
    }

    /**
     * @param array<mixed> $envelope
     */
    private function isSignedEnvelope(array $envelope): bool
    {
        if (($envelope['version'] ?? null) !== self::PAYLOAD_VERSION) {
            return false;
        }

        if (!is_string($envelope['payload'] ?? null) || !is_string($envelope['mac'] ?? null)) {
            return false;
        }

        $expected = hash_hmac('sha256', $envelope['payload'], $this->signatureKey());

        return hash_equals($expected, $envelope['mac']);
    }

    /**
     * @return array{value: mixed, expiresAt: int, version: int}|null
     */
    private function normalizePayload(mixed $payload): ?array
    {
        if (!is_array($payload) || !array_key_exists('value', $payload) || !array_key_exists('expiresAt', $payload) || !array_key_exists('version', $payload)) {
            return null;
        }

        return [
            'value' => $payload['value'],
            'expiresAt' => (int) $payload['expiresAt'],
            'version' => (int) $payload['version'],
        ];
    }

    private function signatureKey(): string
    {
        if ($this->signatureSecret !== '') {
            return hash('sha256', $this->signatureSecret . '|' . $this->namespace, true);
        }

        $appKey = env('APP_KEY');
        if (!is_string($appKey) || trim($appKey) === '') {
            return $this->localSignatureKey();
        }

        return hash('sha256', trim($appKey) . '|' . $this->namespace, true);
    }

    private function localSignatureKey(): string
    {
        $path = $this->root . DIRECTORY_SEPARATOR . '.signature-key';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            $this->ensureDirectory($directory);
        }

        if (!is_file($path)) {
            $secret = bin2hex(random_bytes(32));

            if (file_put_contents($path, $secret, LOCK_EX) === false) {
                throw new \RuntimeException(sprintf('Unable to write cache signing key [%s].', $path));
            }

            @chmod($path, 0600);
        }

        $secret = trim((string) file_get_contents($path));

        if ($secret === '') {
            throw new \RuntimeException(sprintf('Cache signing key [%s] is empty.', $path));
        }

        return hash('sha256', $secret . '|' . $this->namespace, true);
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
