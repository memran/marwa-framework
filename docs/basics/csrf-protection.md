# CSRF Protection

Marwa Framework provides built-in CSRF (Cross-Site Request Forgery) protection to prevent malicious actors from executing unauthorized actions on behalf of authenticated users.

## How It Works

1. A unique CSRF token is generated and stored in the session for each user
2. Forms that modify data (POST, PUT, PATCH, DELETE) must include the token
3. The `SecurityMiddleware` validates the token on protected routes
4. Requests without valid tokens receive a 419 (CSRF Token Mismatch) response

## Default Configuration

CSRF protection is enabled by default with sensible settings:

```php
// config/security.php
return [
    'csrf' => [
        'enabled' => true,
        'field' => '_token',           // Form field name
        'header' => 'X-CSRF-TOKEN',   // Header name for AJAX
        'token' => '__marwa_csrf_token', // Session key
        'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'except' => [],                // Routes excluded from CSRF
    ],
];
```

## Including CSRF Token in Forms

### Using the Helper Function

```php
<!-- Blade/Liquid template -->
<form method="POST" action="/profile">
    {!! csrf_field() !!}
    
    <input type="text" name="name" value="{{ $user->name }}">
    <button type="submit">Update</button>
</form>
```

This generates:

```html
<input type="hidden" name="_token" value="abc123def456...">
```

### Using the Token Directly

```php
// In templates
<input type="hidden" name="_token" value="{{ csrf_token() }}">
```

## AJAX Requests

For AJAX requests, send the token via the `X-CSRF-TOKEN` header:

```javascript
// Using Fetch API
fetch('/api/user', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ name: 'John' })
});
```

### Meta Tag (Recommended)

Add the CSRF token to your layout's `<head>`:

```html
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
```

## Excluding Routes

Exclude routes that don't require CSRF protection (e.g., external webhooks):

```php
// config/security.php
return [
    'csrf' => [
        'enabled' => true,
        'except' => [
            'webhook/stripe',
            'api/external/*',
        ],
    ],
];
```

## Manual Token Validation

Validate tokens programmatically when needed:

```php
use Marwa\Framework\Supports\Helpers\Security;

if (validate_csrf_token($tokenFromRequest)) {
    // Token is valid
} else {
    // Invalid token
}
```

## Rotating Tokens

Rotate the CSRF token after critical operations:

```php
use Marwa\Framework\Facades\Security;

$newToken = Security::rotateCsrfToken();
```

## Disabling CSRF (Not Recommended)

```php
// config/security.php
return [
    'csrf' => [
        'enabled' => false,
    ],
];
```

> **Warning**: Only disable CSRF protection for routes that don't modify data or when using alternative protection mechanisms.

## Security Considerations

- Tokens are 64-character hex strings generated using `random_bytes(32)`
- Comparison uses `hash_equals()` to prevent timing attacks
- Tokens are HTML-escaped when rendered in forms
- Sessions are automatically started when generating tokens
