<?php declare(strict_types=1);

namespace Marwa\App\Http\Response;

/**
 * Value object for HTTP Cookies.
 *
 * Minimal and secure-by-default Set-Cookie builder.
 */
final class Cookie
{
    /** @var string */
    private string $name;
    /** @var string */
    private string $value;
    /** @var int */
    private int $expires;
    /** @var string */
    private string $path;
    /** @var string */
    private string $domain;
    /** @var bool */
    private bool $secure;
    /** @var bool */
    private bool $httpOnly;
    /** @var string|null */
    private ?string $sameSite;

    /**
     * @param string $name
     * @param string $value
     * @param int $minutes TTL in minutes (0 means session cookie)
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param 'Lax'|'Strict'|'None'|null $sameSite
     */
    public function __construct(
        string $name,
        string $value,
        int $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = true,
        bool $httpOnly = true,
        ?string $sameSite = 'Lax'
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Cookie name cannot be empty.');
        }
        $this->name = $name;
        $this->value = rawurlencode($value);
        $this->expires = $minutes > 0 ? time() + ($minutes * 60) : 0;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
    }

    /**
     * Convert to a Set-Cookie header line.
     */
    public function toHeader(): string
    {
        $parts = [];
        $parts[] = "{$this->name}={$this->value}";
        if ($this->expires > 0) {
            $parts[] = 'Expires=' . gmdate('D, d-M-Y H:i:s T', $this->expires);
            $parts[] = 'Max-Age=' . max(0, $this->expires - time());
        }
        if ($this->path !== '') {
            $parts[] = "Path={$this->path}";
        }
        if ($this->domain !== '') {
            $parts[] = "Domain={$this->domain}";
        }
        if ($this->secure) {
            $parts[] = 'Secure';
        }
        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }
        if ($this->sameSite) {
            $parts[] = "SameSite={$this->sameSite}";
        }
        return implode('; ', $parts);
    }
}