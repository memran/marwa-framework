<?php

declare(strict_types=1);

namespace Marwa\App\Session;

use Aura\Session\SessionFactory;
use Aura\Session\Session as AuraSession;
use Aura\Session\Segment as AuraSegment;
use InvalidArgumentException;

/**
 * Laravel-style Session API backed by aura/session.
 *
 * Responsibilities:
 * - Wrap aura/session with a simple, familiar API
 * - Provide flash data lifecycle (next request)
 * - Provide CSRF token helpers
 * - Expose common helpers: pull, push, increment, decrement, remember
 *
 * Usage:
 * $session = new Session(); // or inject custom options
 * $session->put('user.id', 123);
 * $id = $session->get('user.id');
 * $session->flash('status', 'Profile updated');
 * $token = $session->csrfToken();
 */
class Session implements SessionInterface
{
    private const DEFAULT_SEGMENT  = 'app';
    private const FLASH_SEGMENT    = 'app.flash';
    private const FLASH_NOW_KEY    = '_flash_now';
    private const FLASH_KEYS_NEW   = '_new';
    private const FLASH_KEYS_OLD   = '_old';
    private const CSRF_KEY         = '_csrf_token';

    /** @var AuraSession */
    private AuraSession $session;

    /** @var AuraSegment */
    private AuraSegment $segment;

    /** @var AuraSegment */
    private AuraSegment $flash;

    /**
     * @param array{
     *   name?: string,
     *   cookie_lifetime?: int,
     *   cookie_path?: string,
     *   cookie_domain?: string|null,
     *   cookie_secure?: bool,
     *   cookie_httponly?: bool,
     *   cookie_samesite?: 'Lax'|'Strict'|'None',
     *   segment?: string
     * } $options
     */
    public function __construct(array $options = [])
    {
        $factory = new SessionFactory();

        // Map cookie params into aura/session expected shape
        $cookieParams = [
            'lifetime' => $options['cookie_lifetime'] ?? 0,
            'path'     => $options['cookie_path'] ?? '/',
            'domain'   => $options['cookie_domain'] ?? null,
            'secure'   => $options['cookie_secure'] ?? false,
            'httponly' => $options['cookie_httponly'] ?? true,
            'samesite' => $options['cookie_samesite'] ?? 'Lax',
        ];

        $this->session = $factory->newInstance($cookieParams);

        // Set session name if provided (secure default otherwise)
        if (!empty($options['name'])) {
            $this->session->setName($options['name']);
        }

        // Always ensure session is started (idempotent)
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $segmentName = $options['segment'] ?? self::DEFAULT_SEGMENT;
        $this->segment = $this->session->getSegment($segmentName);
        $this->flash   = $this->session->getSegment(self::FLASH_SEGMENT);

        // Age flash data at the beginning of the request lifecycle
        $this->ageFlash();
    }

    /**
     * Get an item from the session.
     *
     * @param string $key dot.notation supported (e.g., "user.profile.name")
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->segment->get($this->rootKey($key));
        return $this->dataGet($value, $this->leafKey($key), $default);
    }

    /**
     * Put a value into the session (overwrites).
     *
     * @param string $key dot.notation supported
     * @param mixed $value
     */
    public function put(string $key, mixed $value): void
    {
        $root = $this->rootKey($key);

        if ($this->isLeaf($key)) {
            $this->segment->set($root, $value);
            return;
        }

        $existing = $this->segment->get($root) ?? [];
        if (!is_array($existing)) {
            $existing = [];
        }

        $this->segment->set($root, $this->dataSet($existing, $this->leafKey($key), $value));
    }

    /**
     * Determine if a key exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__absent__') !== '__absent__';
    }

    /**
     * Remove a key from the session.
     */
    public function forget(string $key): void
    {
        $root = $this->rootKey($key);

        if ($this->isLeaf($key)) {
            $this->segment->set($root, null);
            return;
        }

        $existing = $this->segment->get($root);
        if (is_array($existing)) {
            $existing = $this->dataUnset($existing, $this->leafKey($key));
            $this->segment->set($root, $existing);
        }
    }

    /**
     * Get all data for the bound segment (shallow).
     *
     * @return array<string,mixed>
     */
    public function all(): array
    {
        // Aura segment does not expose all at once; store a shallow bag
        // Use PHP native $_SESSION as last resort for segment scope
        $name = $this->session->getName();
        return $_SESSION[$name] ?? [];
    }

    /**
     * Flash data for the next request.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->flash->set($key, $value);
        $this->trackFlashKey($key, self::FLASH_KEYS_NEW);
    }

    /**
     * Flash data only for the current request.
     */
    public function now(string $key, mixed $value): void
    {
        $now = $this->flash->get(self::FLASH_NOW_KEY) ?? [];
        $now[$key] = $value;
        $this->flash->set(self::FLASH_NOW_KEY, $now);
    }

    /**
     * Reflash all keys for another request cycle.
     */
    public function reflash(): void
    {
        $old = $this->flash->get(self::FLASH_KEYS_OLD) ?? [];
        foreach ($old as $key) {
            $this->trackFlashKey($key, self::FLASH_KEYS_NEW);
        }
        $this->flash->set(self::FLASH_KEYS_OLD, []);
    }

    /**
     * Keep selected flash keys for another request cycle.
     *
     * @param string[] $keys
     */
    public function keep(array $keys): void
    {
        foreach ($keys as $k) {
            $this->trackFlashKey($k, self::FLASH_KEYS_NEW);
        }
    }

    /**
     * Get a flashed value (falls back to "now" data).
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $now = $this->flash->get(self::FLASH_NOW_KEY) ?? [];
        if (array_key_exists($key, $now)) {
            return $now[$key];
        }
        $val = $this->flash->get($key);
        return $val ?? $default;
    }

    /**
     * Retrieve and forget a value atomically.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $val = $this->get($key, $default);
        $this->forget($key);
        return $val;
    }

    /**
     * Push a value onto an array in the session.
     */
    public function push(string $key, mixed $value): void
    {
        $arr = $this->get($key, []);
        if (!is_array($arr)) {
            $arr = [];
        }
        $arr[] = $value;
        $this->put($key, $arr);
    }

    /**
     * Increment a numeric value by $amount (default 1).
     */
    public function increment(string $key, int $amount = 1): int
    {
        $current = (int) $this->get($key, 0);
        $current += $amount;
        $this->put($key, $current);
        return $current;
    }

    /**
     * Decrement a numeric value by $amount (default 1).
     */
    public function decrement(string $key, int $amount = 1): int
    {
        return $this->increment($key, -$amount);
    }

    /**
     * Remember a value for the session lifetime using a callback if absent.
     *
     * @param callable():mixed $callback
     */
    public function remember(string $key, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->put($key, $value);
        return $value;
    }

    /**
     * Regenerate the session ID (prevents fixation).
     */
    public function regenerate(): void
    {
        $this->session->regenerateId();
        // Also rotate CSRF
        $this->regenerateToken();
    }

    /**
     * Invalidate the session entirely (logout flow).
     */
    public function invalidate(): void
    {
        $this->clear();
        $this->session->destroy();
    }

    /**
     * Clear segment data (not the PHP session cookie).
     */
    public function clear(): void
    {
        $this->segment->clear();
        $this->flash->clear();
    }

    /**
     * CSRF: get or create token value.
     */
    public function csrfToken(): string
    {
        $token = (string) ($this->segment->get(self::CSRF_KEY) ?? '');
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $this->segment->set(self::CSRF_KEY, $token);
        }
        return $token;
    }

    /**
     * CSRF: regenerate token.
     */
    public function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->segment->set(self::CSRF_KEY, $token);
        return $token;
    }

    /**
     * CSRF: validate provided token (timing-attack safe).
     */
    public function validateToken(string $provided): bool
    {
        $stored = $this->segment->get(self::CSRF_KEY) ?? '';
        if (!is_string($stored) || $stored === '') {
            return false;
        }
        return hash_equals($stored, $provided);
    }

    /**
     * Get underlying aura/session instance (for advanced use/testing).
     */
    public function raw(): AuraSession
    {
        return $this->session;
    }

    /**
     * Age flash data: move "new" -> "old", clear previous "old", keep "now" for this request only.
     * Call once per request lifecycle (constructor already does this).
     */
    public function ageFlash(): void
    {
        // clear previous old
        $old = $this->flash->get(self::FLASH_KEYS_OLD) ?? [];
        foreach ($old as $key) {
            $this->flash->set($key, null);
        }

        // new becomes old
        $new = $this->flash->get(self::FLASH_KEYS_NEW) ?? [];
        $this->flash->set(self::FLASH_KEYS_OLD, $new);
        $this->flash->set(self::FLASH_KEYS_NEW, []);

        // clear "now" bag (current-request only)
        $this->flash->set(self::FLASH_NOW_KEY, []);
    }

    /**
     * Track a flash key in the lifecycle list.
     */
    private function trackFlashKey(string $key, string $bucket): void
    {
        if (!in_array($bucket, [self::FLASH_KEYS_NEW, self::FLASH_KEYS_OLD], true)) {
            throw new InvalidArgumentException('Invalid flash bucket.');
        }

        $list = $this->flash->get($bucket) ?? [];
        if (!in_array($key, $list, true)) {
            $list[] = $key;
            $this->flash->set($bucket, $list);
        }
    }

    /**
     * Root part of a dot.notation key.
     */
    private function rootKey(string $key): string
    {
        $pos = strpos($key, '.');
        return $pos === false ? $key : substr($key, 0, $pos);
    }

    /**
     * Leaf path of a dot.notation key (after the first segment).
     */
    private function leafKey(string $key): ?string
    {
        $pos = strpos($key, '.');
        return $pos === false ? null : substr($key, $pos + 1);
    }

    /**
     * Whether key has no dot (single segment).
     */
    private function isLeaf(string $key): bool
    {
        return strpos($key, '.') === false;
    }

    /**
     * Safe getter from nested array/object by dot path.
     *
     * @param mixed $target
     */
    private function dataGet(mixed $target, ?string $path, mixed $default): mixed
    {
        if ($path === null || $path === '') {
            return $target ?? $default;
        }

        $segments = explode('.', $path);
        $cursor = $target;

        foreach ($segments as $seg) {
            if (is_array($cursor) && array_key_exists($seg, $cursor)) {
                $cursor = $cursor[$seg];
                continue;
            }
            if (is_object($cursor) && isset($cursor->{$seg})) {
                $cursor = $cursor->{$seg};
                continue;
            }
            return $default;
        }

        return $cursor ?? $default;
    }

    /**
     * Safe setter into nested array by dot path.
     *
     * @param array<string,mixed> $target
     * @return array<string,mixed>
     */
    private function dataSet(array $target, string $path, mixed $value): array
    {
        $segments = explode('.', $path);
        $cursor = &$target;

        foreach ($segments as $seg) {
            if (!is_array($cursor)) {
                $cursor = [];
            }
            if (!array_key_exists($seg, $cursor) || !is_array($cursor[$seg])) {
                $cursor[$seg] = [];
            }
            $cursor = &$cursor[$seg];
        }

        $cursor = $value;
        return $target;
    }

    /**
     * Safe unset from nested array by dot path.
     *
     * @param array<string,mixed> $target
     * @return array<string,mixed>
     */
    private function dataUnset(array $target, string $path): array
    {
        $segments = explode('.', $path);
        $cursor = &$target;

        foreach ($segments as $i => $seg) {
            if ($i === count($segments) - 1) {
                unset($cursor[$seg]);
                break;
            }
            if (!isset($cursor[$seg]) || !is_array($cursor[$seg])) {
                break; // nothing to unset
            }
            $cursor = &$cursor[$seg];
        }

        return $target;
    }
}

/**
 * Minimal interface for DI/testing and swapability.
 */
interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function put(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function forget(string $key): void;
    /** @return array<string,mixed> */
    public function all(): array;

    public function flash(string $key, mixed $value): void;
    public function now(string $key, mixed $value): void;
    public function reflash(): void;
    public function keep(array $keys): void;
    public function getFlash(string $key, mixed $default = null): mixed;

    public function pull(string $key, mixed $default = null): mixed;
    public function push(string $key, mixed $value): void;
    public function increment(string $key, int $amount = 1): int;
    public function decrement(string $key, int $amount = 1): int;
    public function remember(string $key, callable $callback): mixed;

    public function regenerate(): void;
    public function invalidate(): void;
    public function clear(): void;

    public function csrfToken(): string;
    public function regenerateToken(): string;
    public function validateToken(string $provided): bool;

    public function ageFlash(): void;
}
