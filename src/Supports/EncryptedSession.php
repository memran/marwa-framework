<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

use Marwa\Framework\Application;
use Marwa\Framework\Config\SessionConfig;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Support\Json;
use Marwa\Support\Random;

final class EncryptedSession implements SessionInterface
{
    private const SESSION_BAG = '__marwa_encrypted';
    private const FLASH_META = '__flash_meta';
    private const AES_GCM_IV_LENGTH = 12;
    private const AES_GCM_TAG_LENGTH = 16;
    private const AES_GCM_MIN_PAYLOAD_LENGTH = 29;

    private bool $flashAged = false;

    /**
     * @var array{
     *     enabled: bool,
     *     autoStart: bool,
     *     name: string,
     *     lifetime: int,
     *     path: string,
     *     domain: string,
     *     secure: bool,
     *     httpOnly: bool,
     *     sameSite: string,
     *     encrypt: bool,
     *     savePath: string
     * }
     */
    private array $settings;

    public function __construct(
        private Application $app,
        private Config $config
    ) {
        $this->config->loadIfExists(SessionConfig::KEY . '.php');
        $this->settings = array_replace(SessionConfig::defaults($this->app), $this->config->getArray(SessionConfig::KEY, []));
    }

    public function start(): void
    {
        if (!$this->settings['enabled'] || $this->isStarted()) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $this->configureSavePath();

        session_name($this->settings['name']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', $this->settings['httpOnly'] ? '1' : '0');
        ini_set('session.cookie_secure', $this->settings['secure'] ? '1' : '0');
        ini_set('session.cookie_samesite', $this->settings['sameSite']);
        ini_set('session.gc_maxlifetime', (string) $this->settings['lifetime']);

        session_set_cookie_params([
            'lifetime' => $this->settings['lifetime'],
            'path' => $this->settings['path'],
            'domain' => $this->settings['domain'],
            'secure' => $this->settings['secure'],
            'httponly' => $this->settings['httpOnly'],
            'samesite' => $this->settings['sameSite'],
        ]);

        session_start();

        if (!isset($_SESSION[self::SESSION_BAG]) || !is_array($_SESSION[self::SESSION_BAG])) {
            $_SESSION[self::SESSION_BAG] = [];
        }

        if (!$this->flashAged) {
            $this->ageFlashData();
            $this->flashAged = true;
        }
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        $bag = $this->bag();

        if (!array_key_exists($key, $bag)) {
            return $default;
        }

        $decoded = $this->decryptPayload($bag[$key]);

        return $decoded ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[self::SESSION_BAG][$key] = $this->encryptPayload($value);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->set($key, $value);

        $meta = $this->flashMeta();
        $meta['new'] = $this->appendFlashKey($meta['new'], $key);
        $meta['old'] = $this->removeFlashKey($meta['old'], $key);
        $meta['now'] = $this->removeFlashKey($meta['now'], $key);

        $this->storeFlashMeta($meta);
    }

    public function now(string $key, mixed $value): void
    {
        $this->set($key, $value);

        $meta = $this->flashMeta();
        $meta['now'] = $this->appendFlashKey($meta['now'], $key);
        $meta['new'] = $this->removeFlashKey($meta['new'], $key);
        $meta['old'] = $this->removeFlashKey($meta['old'], $key);

        $this->storeFlashMeta($meta);
    }

    public function has(string $key): bool
    {
        $this->start();

        return array_key_exists($key, $this->bag());
    }

    public function all(): array
    {
        $this->start();

        $values = [];

        foreach ($this->bag() as $key => $payload) {
            $value = $this->decryptPayload($payload);

            if ($value !== null) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[self::SESSION_BAG][$key]);

        $meta = $this->flashMeta();
        $meta['new'] = $this->removeFlashKey($meta['new'], $key);
        $meta['old'] = $this->removeFlashKey($meta['old'], $key);
        $meta['now'] = $this->removeFlashKey($meta['now'], $key);
        $this->storeFlashMeta($meta);
    }

    public function keep(?array $keys = null): void
    {
        $this->start();

        $meta = $this->flashMeta();
        $keysToKeep = $keys ?? $meta['old'];

        foreach ($keysToKeep as $key) {
            if (!in_array($key, $meta['old'], true)) {
                continue;
            }

            $meta['new'] = $this->appendFlashKey($meta['new'], $key);
            $meta['old'] = $this->removeFlashKey($meta['old'], $key);
        }

        $this->storeFlashMeta($meta);
    }

    public function reflash(): void
    {
        $this->keep();
    }

    public function flush(): void
    {
        $this->start();
        $_SESSION[self::SESSION_BAG] = [];
    }

    public function regenerate(bool $destroy = false): void
    {
        $this->start();
        session_regenerate_id($destroy);
    }

    public function invalidate(): void
    {
        if (!$this->isStarted()) {
            return;
        }

        $this->flush();
        session_regenerate_id(true);
        session_destroy();
        $this->flashAged = false;
    }

    public function id(): string
    {
        $this->start();

        return session_id();
    }

    public function close(): void
    {
        if ($this->isStarted()) {
            $this->clearNowFlashData();
            session_write_close();
            $this->flashAged = false;
        }
    }

    public function shouldAutoStart(): bool
    {
        return $this->settings['enabled'] && $this->settings['autoStart'];
    }

    /**
     * @return array<string, string>
     */
    public function raw(): array
    {
        $this->start();

        return $this->bag();
    }

    /**
     * @return array<string, string>
     */
    private function bag(): array
    {
        $bag = $_SESSION[self::SESSION_BAG] ?? [];

        return is_array($bag) ? array_filter($bag, 'is_string') : [];
    }

    private function configureSavePath(): void
    {
        $savePath = $this->settings['savePath'];

        if (!is_dir($savePath)) {
            @mkdir($savePath, 0755, true);
        }

        if (!is_dir($savePath) || !is_writable($savePath)) {
            $savePath = ini_get('session.save_path') ?: sys_get_temp_dir();
        }

        ini_set('session.save_path', $savePath);
    }

    private function encryptPayload(mixed $value): string
    {
        $json = Json::encode($value);

        if (!$this->settings['encrypt']) {
            return base64_encode($json);
        }

        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('OpenSSL is required to use encrypted sessions.');
        }

        $iv = Random::bytes(self::AES_GCM_IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $json,
            'aes-256-gcm',
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (!is_string($ciphertext)) {
            throw new \RuntimeException('Unable to encrypt session payload.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    private function decryptPayload(string $payload): mixed
    {
        $decoded = base64_decode($payload, true);

        if (!is_string($decoded)) {
            return null;
        }

        if (!$this->settings['encrypt']) {
            return $this->decodeJson($decoded);
        }

        if (strlen($decoded) < self::AES_GCM_MIN_PAYLOAD_LENGTH) {
            return null;
        }

        $iv = substr($decoded, 0, self::AES_GCM_IV_LENGTH);
        $tag = substr($decoded, self::AES_GCM_IV_LENGTH, self::AES_GCM_TAG_LENGTH);
        $ciphertext = substr($decoded, self::AES_GCM_IV_LENGTH + self::AES_GCM_TAG_LENGTH);

        $json = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (!is_string($json)) {
            return null;
        }

        return $this->decodeJson($json);
    }

    private function encryptionKey(): string
    {
        $appKey = env('APP_KEY');

        if (!is_string($appKey) || trim($appKey) === '') {
            throw new \RuntimeException('APP_KEY must be configured to use encrypted sessions.');
        }

        return hash('sha256', trim($appKey), true);
    }

    private function decodeJson(string $json): mixed
    {
        try {
            return Json::decode($json, true);
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * @return array{new:list<string>,old:list<string>,now:list<string>}
     */
    private function flashMeta(): array
    {
        $raw = $this->getMetaValue(self::FLASH_META);

        if (!is_array($raw)) {
            return [
                'new' => [],
                'old' => [],
                'now' => [],
            ];
        }

        return [
            'new' => $this->normalizeFlashKeys($raw['new'] ?? []),
            'old' => $this->normalizeFlashKeys($raw['old'] ?? []),
            'now' => $this->normalizeFlashKeys($raw['now'] ?? []),
        ];
    }

    /**
     * @param array{new:list<string>,old:list<string>,now:list<string>} $meta
     */
    private function storeFlashMeta(array $meta): void
    {
        $_SESSION[self::SESSION_BAG][self::FLASH_META] = $this->encryptPayload($meta);
    }

    private function ageFlashData(): void
    {
        $meta = $this->flashMeta();

        foreach ($meta['old'] as $key) {
            unset($_SESSION[self::SESSION_BAG][$key]);
        }

        $meta['old'] = $meta['new'];
        $meta['new'] = [];

        $this->storeFlashMeta($meta);
    }

    private function clearNowFlashData(): void
    {
        $meta = $this->flashMeta();

        foreach ($meta['now'] as $key) {
            unset($_SESSION[self::SESSION_BAG][$key]);
        }

        $meta['now'] = [];
        $this->storeFlashMeta($meta);
    }

    private function getMetaValue(string $key): mixed
    {
        $bag = $this->bag();

        if (!array_key_exists($key, $bag)) {
            return null;
        }

        return $this->decryptPayload($bag[$key]);
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeFlashKeys(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter($value, static fn (mixed $item): bool => is_string($item) && $item !== '')));
    }

    /**
     * @param list<string> $keys
     * @return list<string>
     */
    private function appendFlashKey(array $keys, string $key): array
    {
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @param list<string> $keys
     * @return list<string>
     */
    private function removeFlashKey(array $keys, string $key): array
    {
        return array_values(array_filter($keys, static fn (string $current): bool => $current !== $key));
    }
}
