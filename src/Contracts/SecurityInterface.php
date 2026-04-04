<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface SecurityInterface
{
    /**
     * @return array{
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
    public function configuration(): array;

    public function isEnabled(): bool;

    public function csrfEnabled(): bool;

    public function throttleEnabled(): bool;

    public function csrfToken(): string;

    public function rotateCsrfToken(): string;

    public function validateCsrfToken(string $token): bool;

    public function csrfField(): string;

    public function isCsrfProtected(string $method, string $path): bool;

    public function isTrustedHost(string $host): bool;

    public function isTrustedOrigin(string $origin): bool;

    public function throttle(string $key, ?int $limit = null, ?int $window = null): bool;

    public function throttleLimit(): int;

    public function throttleWindow(): int;

    public function sanitizeFilename(string $name): string;

    public function safePath(string $path, string $basePath): string;
}
