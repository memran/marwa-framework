# Security Best Practices

This guide covers security best practices for building secure applications with Marwa Framework.

## Input Validation

### Always Validate Input

```php
use Marwa\Framework\Validation\RequestValidator;

$validator = new RequestValidator();

$rules = [
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
    'name' => 'required|string|max:100',
];

$errors = $validator->validate($request->all(), $rules);

if ($errors->any()) {
    return redirect('/form')->withErrors($errors);
}
```

### Whitelist Input

```php
// Only allow specific values
$allowedTypes = ['image', 'document', 'video'];
$type = $request->input('type');

if (!in_array($type, $allowedTypes, true)) {
    throw new InvalidArgumentException('Invalid type');
}
```

## SQL Injection Prevention

### Use Parameterized Queries

```php
// WRONG - SQL injection vulnerable
$query = "SELECT * FROM users WHERE id = " . $id;

// CORRECT - Parameterized query
$users = db()
    ->connection()
    ->select("SELECT * FROM users WHERE id = ?", [$id]);
```

### Use Query Builder

```php
// Safe query building
$users = User::where('email', $email)
    ->where('active', true)
    ->get();
```

## Password Security

### Hashing Passwords

```php
// Hash when creating/updating
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verify when authenticating
if (password_verify($input, $storedHash)) {
    // Success
}

// Check if needs rehashing
if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
    // Update hash
}
```

## XSS Prevention

### Escape Output

```php
// In Twig templates - auto-escaped
{{ user.name }}
{{ user.bio | e }}

// In Liquid templates
{{ user.name | escape }}

// Raw output (only when trusted)
{!! $htmlContent !!}
```

### Content Security Policy

```php
// config/security.php
return [
    'csp' => [
        'enabled' => true,
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline'",
        'style-src' => "'self' 'unsafe-inline'",
    ],
];
```

## Session Security

### Secure Session Configuration

```php
// config/session.php
return [
    'secure' => true,        // HTTPS only
    'httpOnly' => true,      // No JS access
    'sameSite' => 'Strict',  // CSRF protection
    'encrypt' => true,       // Encrypted data
];
```

### Session Regeneration

```php
// On login
session()->regenerate();
session()->set('user_id', $user->id);

// On logout
session()->invalidate();
```

## File Upload Security

### Validate File Uploads

```php
$file = $request->file('upload');

if ($file === null) {
    return response()->json(['error' => 'No file uploaded'], 400);
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
if (!in_array($file->getMimeType(), $allowedTypes, true)) {
    throw new InvalidArgumentException('Invalid file type');
}

// Validate file size (max 5MB)
if ($file->getSize() > 5 * 1024 * 1024) {
    throw new InvalidArgumentException('File too large');
}
```

### Store Outside Web Root

```php
// Store files outside public/
$path = storage_path('uploads/' . $filename);

// Generate public URL separately
$url = '/storage/uploads/' . $filename;
```

## HTTPS Enforcement

### Force HTTPS

```php
// In SecurityMiddleware or .htaccess
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### Security Headers

The framework automatically adds security headers:

```php
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

## API Security

### Rate Limiting

```php
// config/security.php
'throttle' => [
    'enabled' => true,
    'limit' => 60,    // requests
    'window' => 60,   // per seconds
],
```

### CORS Configuration

```php
// config/cors.php
return [
    'allowedOrigins' => ['https://example.com'],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowedHeaders' => ['Content-Type', 'Authorization'],
    'maxAge' => 3600,
];
```

## Error Handling

### Don't Expose Errors

```env
APP_DEBUG=false
APP_ENV=production
```

### Log Errors Securely

```php
logger()->error('Error occurred', [
    'user_id' => auth()->id(),
    'path' => request()->getUri()->getPath(),
    // Don't log passwords or sensitive data
]);
```

## Dependency Security

### Keep Dependencies Updated

```bash
composer audit
composer update --dry-run
```

### Review Permissions

Only request necessary permissions:

```json
{
    "name": "myapp/package",
    "permissions": ["filesystem", "network"]
}
```

## Security Checklist

- [ ] All user input is validated
- [ ] SQL queries use parameterized statements
- [ ] Passwords are hashed with `password_hash()`
- [ ] Output is escaped appropriately
- [ ] Sessions are configured securely
- [ ] HTTPS is enforced in production
- [ ] Security headers are set
- [ ] File uploads are validated
- [ ] Rate limiting is enabled
- [ ] Errors don't expose stack traces
- [ ] Dependencies are audited regularly
- [ ] CSRF protection is enabled
- [ ] Authentication is implemented
- [ ] Authorization checks are in place
