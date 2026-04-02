# Quick Start

## Install

```bash
composer require memran/marwa-framework
cp .env.example .env
```

## Bootstrap

Create an application instance and boot it:

```php
use Marwa\Framework\Application;

$app = new Application(__DIR__);
$app->boot();
```

## Register Routes

Add routes in `routes/web.php`:

```php
use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/health', fn () => Response::json(['ok' => true]))->register();
```

## Handle Requests

Use the HTTP kernel from your host application's entrypoint:

```php
use Marwa\Framework\HttpKernel;

$kernel = $app->make(HttpKernel::class);
$response = $kernel->handle($app->make(\Marwa\Framework\Adapters\HttpRequestFactory::class)->request());
$kernel->terminate($response);
```

## Run Quality Checks

```bash
composer test
composer analyse
composer lint
```
