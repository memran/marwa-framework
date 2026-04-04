# Security

The security service stays close to HTTP concerns and works alongside `marwa-router`.

## CSRF

Enable CSRF in `config/security.php`:

```php
return [
    'enabled' => true,
    'csrf' => [
        'enabled' => true,
        'except' => ['webhook/*'],
    ],
];
```

Render a hidden token field in forms:

```php
<form method="post">
    <?= csrf_field() ?>
</form>
```

Validate a token manually when needed:

```php
if (!validate_csrf_token($request->getHeaderLine('X-CSRF-TOKEN'))) {
    throw new RuntimeException('Invalid CSRF token');
}
```

## Trusted Hosts and Origins

```php
return [
    'trustedHosts' => ['example.com', '*.example.com'],
    'trustedOrigins' => ['https://example.com'],
];
```

## Throttling

```php
if (!throttle('login:' . $ip, 10, 60)) {
    return response('Too many attempts', 429);
}
```

## Risk Logging and Cron Reporting

Security risk events are written to `storage/security/risk.jsonl` by default. The middleware records suspicious requests for untrusted hosts, origins, CSRF mismatches, and throttle breaches.

Generate a summary from cron:

```bash
php bin/console security:report --since-hours=24 --prune-days=30
```

Use `--json` if another job or dashboard consumes the output:

```bash
php bin/console security:report --json
```

## Safe Paths

```php
$name = sanitize_filename($uploadName);
$path = safe_path('uploads/' . $name, storage_path('app'));
```
