<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

use MatthiasMullie\Scrapbook\KeyValueStore;

final class NatsStore implements KeyValueStore
{
    private const EXPIRE_RELATIVE_THRESHOLD = 2592000;
    private const PAYLOAD_VERSION = 1;
    private const CAS_ATTEMPTS = 3;

    public function __construct(
        private NatsBucketInterface $bucket,
        private string $namespace = '',
        private string $signatureSecret = ''
    ) {
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
            $token = null;

            return false;
        }

        $token = $payload['version'];

        return $payload['value'];
    }

    /**
     * @param list<string> $keys
     * @param-out array<string, int>|null $tokens
     * @return array<string, mixed>
     */
    public function getMulti(array $keys, ?array &$tokens = null): array
    {
        $values = [];
        $tokens = null;

        foreach ($keys as $key) {
            $key = (string) $key;
            $payload = $this->readPayload($key);

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
        $this->validateKey($key);

        if ($this->isExpiredAtWriteTime($expire)) {
            return $this->delete($key);
        }

        return $this->bucket->put($this->fullKey($key), $this->encodePayload($value, $expire));
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
        $this->validateKey($key);

        return $this->bucket->delete($this->fullKey($key));
    }

    public function deleteMulti(array $keys): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->delete((string) $key);
        }

        return $results;
    }

    public function validateKeyOrFail(string $key): bool
    {
        return $key !== '';
    }

    public function add(string $key, mixed $value, int $expire = 0): bool
    {
        $this->validateKey($key);

        if ($this->readPayload($key) !== null) {
            return false;
        }

        if ($this->isExpiredAtWriteTime($expire)) {
            return true;
        }

        return $this->bucket->create($this->fullKey($key), $this->encodePayload($value, $expire));
    }

    public function replace(string $key, mixed $value, int $expire = 0): bool
    {
        $this->validateKey($key);

        $payload = $this->readPayload($key);

        if ($payload === null) {
            return false;
        }

        if ($this->isExpiredAtWriteTime($expire)) {
            return $this->delete($key);
        }

        return $this->bucket->update($this->fullKey($key), $this->encodePayload($value, $expire), $payload['version']);
    }

    public function cas(mixed $token, string $key, mixed $value, int $expire = 0): bool
    {
        $this->validateKey($key);

        if (!is_int($token) && !ctype_digit((string) $token)) {
            return false;
        }

        if ($this->isExpiredAtWriteTime($expire)) {
            return $this->bucket->update($this->fullKey($key), $this->encodePayload($value, $expire), (int) $token);
        }

        return $this->bucket->update($this->fullKey($key), $this->encodePayload($value, $expire), (int) $token);
    }

    public function increment(string $key, int $offset = 1, int $initial = 0, int $expire = 0): int|false
    {
        if ($offset <= 0 || $initial < 0) {
            return false;
        }

        return $this->changeNumericValue($key, $offset, $initial, $expire);
    }

    public function decrement(string $key, int $offset = 1, int $initial = 0, int $expire = 0): int|false
    {
        if ($offset <= 0 || $initial < 0) {
            return false;
        }

        return $this->changeNumericValue($key, -$offset, $initial, $expire);
    }

    public function touch(string $key, int $expire): bool
    {
        $this->validateKey($key);

        $payload = $this->readPayload($key);

        if ($payload === null) {
            return false;
        }

        return $this->bucket->update($this->fullKey($key), $this->encodePayload($payload['value'], $expire), $payload['version']);
    }

    public function flush(): bool
    {
        $success = true;

        foreach (array_keys($this->bucket->all()) as $key) {
            if (!$this->ownsKey((string) $key)) {
                continue;
            }

            $success = $this->bucket->purge((string) $key) && $success;
        }

        return $success;
    }

    public function getCollection(string $name): KeyValueStore
    {
        $collection = trim($name);
        $namespace = $this->namespace === ''
            ? $collection
            : $this->namespace . ':' . $collection;

        return new self($this->bucket, $namespace, $this->signatureSecret);
    }

    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Cache key cannot be empty.');
        }
    }

    /**
     * @return array{value: mixed, expiresAt: int, version: int}|null
     */
    private function readPayload(string $key): ?array
    {
        $entry = $this->bucket->getEntry($this->fullKey($key));

        if ($entry === null) {
            return null;
        }

        $payload = $this->decodePayload($entry->value);

        if ($payload === null) {
            return null;
        }

        if ($payload['expiresAt'] > 0 && $payload['expiresAt'] <= time()) {
            $this->bucket->delete($this->fullKey($key));

            return null;
        }

        return [
            'value' => $payload['value'],
            'expiresAt' => $payload['expiresAt'],
            'version' => $entry->revision,
        ];
    }

    private function changeNumericValue(string $key, int $offset, int $initial, int $expire): int|false
    {
        $this->validateKey($key);

        for ($attempt = 0; $attempt < self::CAS_ATTEMPTS; $attempt++) {
            $payload = $this->readPayload($key);

            if ($payload === null) {
                if ($this->add($key, $initial, $expire)) {
                    return $initial;
                }

                continue;
            }

            if (!is_numeric($payload['value']) || $payload['value'] < 0) {
                return false;
            }

            $value = max(0, ((int) $payload['value']) + $offset);

            if ($this->bucket->update($this->fullKey($key), $this->encodePayload($value, $expire), $payload['version'])) {
                return $value;
            }
        }

        return false;
    }

    private function fullKey(string $key): string
    {
        return $this->namespace === '' ? $key : $this->namespace . ':' . $key;
    }

    private function ownsKey(string $key): bool
    {
        return $this->namespace === '' || str_starts_with($key, $this->namespace . ':');
    }

    private function encodePayload(mixed $value, int $expire): string
    {
        $payload = [
            'version' => self::PAYLOAD_VERSION,
            'value' => $value,
            'expiresAt' => $this->normalizeExpire($expire),
        ];
        $serialized = serialize($payload);

        return serialize([
            'version' => self::PAYLOAD_VERSION,
            'payload' => $serialized,
            'mac' => hash_hmac('sha256', $serialized, $this->signatureKey()),
        ]);
    }

    /**
     * @return array{value: mixed, expiresAt: int}|null
     */
    private function decodePayload(string $contents): ?array
    {
        try {
            $envelope = unserialize($contents, ['allowed_classes' => false]);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($envelope) || !$this->isSignedEnvelope($envelope)) {
            return null;
        }

        try {
            $payload = unserialize($envelope['payload'], ['allowed_classes' => false]);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($payload) || !array_key_exists('value', $payload) || !array_key_exists('expiresAt', $payload)) {
            return null;
        }

        return [
            'value' => $payload['value'],
            'expiresAt' => (int) $payload['expiresAt'],
        ];
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

    private function signatureKey(): string
    {
        if ($this->signatureSecret !== '') {
            return hash('sha256', $this->signatureSecret . '|' . $this->namespace, true);
        }

        $appKey = env('APP_KEY');
        if (!is_string($appKey) || trim($appKey) === '') {
            throw new \RuntimeException('APP_KEY or an explicit cache signing secret must be configured to use NATS cache.');
        }

        return hash('sha256', trim($appKey) . '|' . $this->namespace, true);
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

    private function isExpiredAtWriteTime(int $expire): bool
    {
        return $this->normalizeExpire($expire) > 0 && $this->normalizeExpire($expire) <= time();
    }
}
