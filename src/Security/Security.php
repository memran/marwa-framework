<?php

declare(strict_types=1);

namespace Marwa\Framework\Security;

use Marwa\Framework\Application;
use Marwa\Framework\Config\SecurityConfig;
use Marwa\Framework\Contracts\CacheInterface;
use Marwa\Framework\Contracts\SecurityInterface;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Exceptions\InvalidArgumentException;
use Marwa\Framework\Supports\Config;
use Marwa\Support\Html;
use Marwa\Support\Random;
use Marwa\Support\Str;

final class Security implements SecurityInterface
{
    /**
     * @var array{
     *     enabled: bool,
     *     csrf: array{
     *         enabled: bool,
     *         field: string,
     *         header: string,
     *         token: string,
     *         methods: list<string>,
     *         except: list<string>
     *     },
     *     trustedHosts: list<string>,
     *     trustedOrigins: list<string>,
     *     throttle: array{
     *         enabled: bool,
     *         prefix: string,
     *         limit: int,
     *         window: int
     *     }
     * }
     */
    private array $settings;

    public function __construct(
        private Application $app,
        private Config $config,
        private CacheInterface $cache,
        private SessionInterface $session
    ) {
        $this->config->loadIfExists(SecurityConfig::KEY . '.php');
        $this->settings = array_replace_recursive(SecurityConfig::defaults($this->app), $this->config->getArray(SecurityConfig::KEY, []));
    }

    public function configuration(): array
    {
        return $this->settings;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->settings['enabled'];
    }

    public function csrfEnabled(): bool
    {
        return $this->isEnabled() && (bool) $this->settings['csrf']['enabled'];
    }

    public function throttleEnabled(): bool
    {
        return $this->isEnabled() && (bool) $this->settings['throttle']['enabled'];
    }

    public function csrfToken(): string
    {
        $this->session->start();

        $token = $this->session->get($this->csrfSessionKey());

        if (!is_string($token) || $token === '') {
            $token = $this->generateToken();
            $this->session->set($this->csrfSessionKey(), $token);
        }

        return $token;
    }

    public function rotateCsrfToken(): string
    {
        $token = $this->generateToken();
        $this->session->start();
        $this->session->set($this->csrfSessionKey(), $token);

        return $token;
    }

    public function validateCsrfToken(string $token): bool
    {
        if (!$this->csrfEnabled()) {
            return true;
        }

        $current = $this->csrfToken();

        return $token !== '' && hash_equals($current, $token);
    }

    public function csrfField(): string
    {
        $field = $this->settings['csrf']['field'];
        return Html::input('hidden', $field, $this->csrfToken());
    }

    public function isCsrfProtected(string $method, string $path): bool
    {
        if (!$this->csrfEnabled()) {
            return false;
        }

        $method = strtoupper($method);

        if (!in_array($method, $this->settings['csrf']['methods'], true)) {
            return false;
        }

        foreach ($this->settings['csrf']['except'] as $pattern) {
            if ($this->pathMatches($path, $pattern)) {
                return false;
            }
        }

        return true;
    }

    public function isTrustedHost(string $host): bool
    {
        $trustedHosts = $this->settings['trustedHosts'];

        if ($trustedHosts === []) {
            return true;
        }

        return $this->matchesAny($host, $trustedHosts);
    }

    public function isTrustedOrigin(string $origin): bool
    {
        $trustedOrigins = $this->settings['trustedOrigins'];

        if ($trustedOrigins === []) {
            return true;
        }

        $origin = trim($origin);

        if ($origin === '') {
            return false;
        }

        return $this->matchesAny($origin, $trustedOrigins);
    }

    public function throttle(string $key, ?int $limit = null, ?int $window = null): bool
    {
        if (!$this->throttleEnabled()) {
            return true;
        }

        $limit ??= $this->throttleLimit();
        $window ??= $this->throttleWindow();

        if ($limit <= 0 || $window <= 0) {
            return true;
        }

        $cacheKey = $this->throttleCacheKey($key);
        $current = (int) $this->cache->get($cacheKey, 0);

        if ($current >= $limit) {
            return false;
        }

        $this->cache->put($cacheKey, $current + 1, $window);

        return true;
    }

    public function throttleLimit(): int
    {
        return max(1, (int) $this->settings['throttle']['limit']);
    }

    public function throttleWindow(): int
    {
        return max(1, (int) $this->settings['throttle']['window']);
    }

    public function sanitizeFilename(string $name): string
    {
        $name = basename(str_replace(["\0", '\\'], '/', $name));
        $name = preg_replace('/[^\pL\pN._-]+/u', '-', $name) ?? $name;
        $name = trim($name, ".-_ \t\n\r\0\x0B");

        return $name !== '' ? $name : 'file';
    }

    public function safePath(string $path, string $basePath): string
    {
        $basePath = $this->normalizePath($basePath);
        $candidate = $this->normalizePath($basePath . DIRECTORY_SEPARATOR . ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));

        if ($candidate === $basePath || Str::startsWith($candidate, $basePath . DIRECTORY_SEPARATOR)) {
            return $candidate;
        }

        throw new InvalidArgumentException(sprintf('The path [%s] escapes the base path [%s].', $path, $basePath));
    }

    private function csrfSessionKey(): string
    {
        return (string) $this->settings['csrf']['token'];
    }

    private function generateToken(): string
    {
        return bin2hex(Random::bytes(32));
    }

    /**
     * @param list<string> $patterns
     */
    private function matchesAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->wildcardMatch($value, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function wildcardMatch(string $value, string $pattern): bool
    {
        $pattern = trim($pattern);

        if ($pattern === '') {
            return false;
        }

        if ($pattern === '*') {
            return true;
        }

        $quoted = preg_quote(Str::lower($pattern), '#');
        $quoted = str_replace('\*', '.*', $quoted);

        return (bool) preg_match('#^' . $quoted . '$#', Str::lower($value));
    }

    private function pathMatches(string $path, string $pattern): bool
    {
        return $this->wildcardMatch(ltrim($path, '/'), ltrim($pattern, '/'));
    }

    private function throttleCacheKey(string $key): string
    {
        $prefix = (string) $this->settings['throttle']['prefix'];
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9]/', '-', ltrim($key, ':'));

        return trim($prefix . '-' . $sanitizedKey, '-');
    }

    private function normalizePath(string $path): string
    {
        $path = preg_replace('#[\\\\/]+#', DIRECTORY_SEPARATOR, $path) ?? $path;
        $path = trim($path);

        $isAbsolute = Str::startsWith($path, DIRECTORY_SEPARATOR);
        $segments = explode(DIRECTORY_SEPARATOR, $path);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($resolved);
                continue;
            }

            $resolved[] = $segment;
        }

        $normalized = implode(DIRECTORY_SEPARATOR, $resolved);

        return $isAbsolute ? DIRECTORY_SEPARATOR . $normalized : $normalized;
    }
}
