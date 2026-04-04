# Security API

`Marwa\Framework\Security\Security` implements `Marwa\Framework\Contracts\SecurityInterface`.

## Access

```php
$security = security();
$token = $security->csrfToken();
```

## Methods

- `configuration()`: returns the merged security config
- `csrfToken()`: gets or creates the current CSRF token
- `rotateCsrfToken()`: replaces the current token
- `validateCsrfToken(string $token)`: validates a token against session state
- `csrfField()`: returns a hidden input field for forms
- `isTrustedHost(string $host)`: validates the request host
- `isTrustedOrigin(string $origin)`: validates the request origin
- `throttle(string $key, ?int $limit = null, ?int $window = null)`: rate limits a key through the shared cache
- `sanitizeFilename(string $name)`: strips unsafe filename characters
- `safePath(string $path, string $basePath)`: prevents path traversal outside a base directory

## Risk Analyzer

`Marwa\Framework\Security\RiskAnalyzer` records suspicious request signals to a JSONL journal and produces compact summaries for cron jobs.

- `record(string $category, string $message, array $context = [], int $score = 50)`: write a risk signal and log it through PSR-3
- `recordRequest(ServerRequestInterface $request, string $category, string $message, array $context = [], int $score = 50)`: capture request metadata with the signal
- `report(?int $sinceHours = null)`: summarize counts by category and score bucket
- `prune(?int $olderThanDays = null)`: remove stale signals from the journal
- `logPath()`: return the current JSONL journal path
