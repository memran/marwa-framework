# HTTP Kernel Flow

## Request Lifecycle

The framework HTTP flow is:

1. `Application` boots the container, environment, config, logger, and events.
2. `HttpKernel` registers service providers and middleware.
3. `RelayPipelineAdapter` runs global middleware in order.
4. `RouterMiddleware` dispatches the request through `marwa-router`.
5. `HttpKernel::terminate()` emits the final response.

## Default Middleware

Unless the consuming app overrides `app.middlewares`, the kernel uses:

- `RequestIdMiddleware`
- `MaintenanceMiddleware`
- `RouterMiddleware`
- `DebugbarMiddleware`

## Custom Not Found Handling

`HttpKernel::setNotFound()` delegates to the router’s built-in not-found handler:

```php
$kernel->setNotFound(function ($request) {
    return \Marwa\Router\Response::json([
        'error' => 'Route not found',
        'path' => $request->getUri()->getPath(),
    ], 404);
});
```

## Safe Defaults

- Missing consumer config files are ignored instead of crashing bootstrap
- Invalid middleware/provider entries are skipped and logged
- Request IDs are validated and generated securely when absent
