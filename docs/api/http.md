# HTTP API

## `Marwa\Framework\HttpKernel`

### `handle(ServerRequestInterface $request): ResponseInterface`

```php
$response = $kernel->handle($request);
```

Short description:
Dispatches the boot event and runs the configured middleware pipeline.

### `terminate(ResponseInterface $response): void`

```php
$kernel->terminate($response);
```

Short description:
Dispatches the terminate event and emits the response.

### `setNotFound(callable $handler): void`

```php
$kernel->setNotFound(fn ($request) => \Marwa\Router\Response::notFound());
```

Short description:
Registers a router-backed not-found handler through `marwa-router`.

## `Marwa\Framework\Adapters\HttpRequestFactory`

### `request(): ServerRequestInterface`

```php
$request = $app->make(\Marwa\Framework\Adapters\HttpRequestFactory::class)->request();
```

Short description:
Creates a PSR-7 server request from globals.

## Middleware

### `RequestIdMiddleware`

Short description:
Attaches a request ID to the request and response. Uses an incoming `X-Request-ID` when valid; otherwise generates a secure ID.

### `MaintenanceMiddleware`

Short description:
Returns `503` when `MAINTENANCE` is enabled.

### `RouterMiddleware`

Short description:
Dispatches the request to the router and renders a safe 404 response when a route is missing.

### `DebugbarMiddleware`

Short description:
Injects the debug bar into HTML responses when debugging is enabled.
