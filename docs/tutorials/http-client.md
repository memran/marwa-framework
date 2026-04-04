# HTTP Client Tutorial

## Overview

The framework exposes a Guzzle-backed HTTP client through `app()->http()`, the `http()` helper, and the `Marwa\Framework\Facades\Http` facade. It is intended for outbound API calls, not request handling inside the kernel.

## Basic Requests

```php
http()->get('https://example.com/status');
http()->post('https://example.com/users', [
    'json' => ['name' => 'Marwa'],
]);
```

## Named Clients

Define reusable API profiles in `config/http.php`:

```php
return [
    'default' => 'github',
    'clients' => [
        'github' => [
            'base_uri' => 'https://api.github.com',
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
            ],
        ],
    ],
];
```

Then use the profile by name:

```php
http()->withClient('github')->get('/repos/memran/marwa-framework');
```

## Fluent Request Defaults

The client supports lightweight request builders:

```php
http()
    ->withHeaders(['X-Trace-ID' => 'abc123'])
    ->timeout(10)
    ->verify(true)
    ->json('POST', '/api/users', ['name' => 'Marwa']);
```

Use `form()` for `application/x-www-form-urlencoded` payloads and `multipart()` for file uploads.
