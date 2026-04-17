# HTTP Entry Point (public/index.php)

The `public/index.php` file is the entry point for all HTTP requests. This guide shows complete examples for different use cases.

## Minimal Example

The simplest entry point for a basic application:

```php
<?php

declare(strict_types=1);

use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Adapters\HttpRequestFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/..');
$app->boot();

$kernel = $app->make(HttpKernel::class);
$request = $app->make(HttpRequestFactory::class)->request();

$response = $kernel->handle($request);
$kernel->terminate($response);
```

## Complete Example with Error Handling

A production-ready entry point with error handling:

```php
<?php

declare(strict_types=1);

use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Adapters\HttpRequestFactory;
use Marwa\Router\Response;
use Throwable;

define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

require __DIR__ . '/../vendor/autoload.php';

try {
    // Create and bootstrap the application
    $app = new Application(__DIR__ . '/..');
    $app->boot();

    // Create the HTTP kernel
    $kernel = $app->make(HttpKernel::class);

    // Create the HTTP request from globals
    $request = $app->make(HttpRequestFactory::class)->request();

    // Handle the request and get response
    $response = $kernel->handle($request);

    // Send the response
    $kernel->terminate($response);

} catch (Throwable $e) {
    // Global error handling for uncaught exceptions
    http_response_code(500);
    
    if (class_exists(Response::class)) {
        $response = Response::html(
            '<h1>Application Error</h1>' .
            '<p>' . htmlspecialchars($e->getMessage()) . '</p>' .
            '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>',
            500
        );
        $response->getBody()->rewind();
        echo $response->getBody()->getContents();
    } else {
        echo '<h1>Application Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
```

## Example with Session and Middleware

An entry point with explicit session and middleware configuration:

```php
<?php

declare(strict_types=1);

use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Adapters\HttpRequestFactory;
use Marwa\Framework\Middlewares\SessionMiddleware;
use Marwa\Framework\Middlewares\SecurityMiddleware;
use Marwa\Framework\Middlewares\RouterMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/..');
$app->boot();

// Start session before handling request
$app->make(SessionMiddleware::class)->start();

// Handle the request
$kernel = $app->make(HttpKernel::class);
$request = $app->make(HttpRequestFactory::class)->request();
$response = $kernel->handle($request);
$kernel->terminate($response);
```

## Example with JSON API

An entry point optimized for JSON APIs:

```php
<?php

declare(strict_types=1);

use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Adapters\HttpRequestFactory;
use Marwa\Router\Response;
use Throwable;
use JsonException;

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

try {
    $app = new Application(__DIR__ . '/..');
    $app->boot();

    $kernel = $app->make(HttpKernel::class);
    $request = $app->make(HttpRequestFactory::class)->request();

    $response = $kernel->handle($request);

    // Always return JSON for API
    if ($response->getHeaderLine('Content-Type') === '') {
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $response = Response::json($body, $response->getStatusCode());
    }

    $kernel->terminate($response);

} catch (JsonException $e) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid JSON',
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server Error',
        'message' => $e->getMessage()
    ]);
}
```

## Example with Debug Mode

An entry point with debug information:

```php
<?php

declare(strict_types=1);

use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;
use Marwa\Framework\Adapters\HttpRequestFactory;
use Throwable;

define('START_TIME', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(__DIR__ . '/..');
$app->boot();

// Check if in debug mode
$debug = $app->make(\Marwa\Framework\Supports\Config::class)->get('app.debug', false);

try {
    $kernel = $app->make(HttpKernel::class);
    $request = $app->make(HttpRequestFactory::class)->request();
    $response = $kernel->handle($request);
    $kernel->terminate($response);

} catch (Throwable $e) {
    http_response_code(500);
    
    $error = [
        'message' => $debug ? $e->getMessage() : 'Internal Server Error',
        'file' => $debug ? $e->getFile() : null,
        'line' => $debug ? $e->getLine() : null,
    ];
    
    if ($debug) {
        $error['trace'] = $e->getTrace();
    }
    
    header('Content-Type: application/json');
    echo json_encode($error, $debug ? JSON_PRETTY_PRINT : 0);
}
```

## Apache Configuration (public/.htaccess)

For Apache servers, create `public/.htaccess`:

```apache
RewriteEngine On

# Redirect Trailing Slashes...
RewriteRule ^(.*)/$ /$1 [L,R=301]

# Handle Front Controller...
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]

# Deny access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

## Nginx Configuration

For Nginx servers, use this configuration:

```nginx
server {
    listen 80;
    server_name example.com;
    root /path/to/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known) {
        deny all;
    }

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

## Testing Your Entry Point

Create a test file `tests/Feature/HttpEntryPointTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class HttpEntryPointTest extends TestCase
{
    public function testIndexPhpExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../public/index.php');
    }

    public function testIndexPhpIsReadable(): void
    {
        $this->assertIsReadable(__DIR__ . '/../../public/index.php');
    }

    public function testIndexPhpContainsRequiredImports(): void
    {
        $content = file_get_contents(__DIR__ . '/../../public/index.php');
        
        $this->assertStringContainsString('Marwa\\Framework\\Application', $content);
        $this->assertStringContainsString('HttpKernel', $content);
        $this->assertStringContainsString('autoload.php', $content);
    }
}
```

## Next Steps

- [Quick Start Guide](../tutorials/quick-start.md) - Build your first app
- [Routing](../tutorials/controllers.md) - Define routes
- [Controllers](../tutorials/controllers.md) - Handle requests
- [Middleware](../reference/middleware.md) - Process requests
