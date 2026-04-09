# Error Pages

The framework provides configurable error pages for HTTP errors and maintenance mode.

## Configuration

Error page templates are configured in `config/app.php`:

```php
return [
    // ... other config
    
    'error404' => [
        'template' => 'errors/404.twig',
    ],
    
    'maintenance' => [
        'template' => 'maintenance.twig',
        'message' => 'Service temporarily unavailable for maintenance',
    ],
];
```

## 404 Page

When a route is not found, the framework renders a 404 error page.

### Template Variables

| Variable | Description |
|----------|-------------|
| `{{ path }}` | The requested URL path |
| `{{ method }}` | The HTTP method (GET, POST, etc.) |
| `{{ debug }}` | Boolean for debug mode |

### Example Template

Create `resources/views/errors/404.twig`:

```twig
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found</title>
</head>
<body>
    <h1>404</h1>
    <p>Sorry, the page <code>{{ path }}</code> was not found.</p>
    <p>Method: {{ method }}</p>
    
    {% if debug %}
    <div class="debug">
        <p>Debug mode is enabled</p>
    </div>
    {% endif %}
</body>
</html>
```

### Disable Custom Template

To disable the custom template and use the default inline HTML:

```php
'error404' => [
    'template' => null,
],
```

## Maintenance Page

When `MAINTENANCE=1` is set in the environment, all requests return a 503 maintenance page.

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `MAINTENANCE` | Enable maintenance mode (0 or 1) | 0 |
| `MAINTENANCE_TIME` | Estimated recovery time in seconds | 300 |

### Template Variables

| Variable | Description |
|----------|-------------|
| `{{ message }}` | The maintenance message from config |
| `{{ estimated_recovery }}` | ISO 8601 formatted recovery time |

### Example Template

Create `resources/views/maintenance.twig`:

```twig
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Under Maintenance</title>
</head>
<body>
    <h1>Under Maintenance</h1>
    <p>{{ message }}</p>
    <p>Estimated recovery: {{ estimated_recovery }}</p>
</body>
</html>
```

### Disable Custom Template

To disable the custom template and use the default inline HTML:

```php
'maintenance' => [
    'template' => null,
    'message' => 'We will be back soon!',
],
```

## Default Templates

The framework includes default templates in `resources/views/`:

- `errors/404.twig` - Default 404 page
- `maintenance.twig` - Default maintenance page

These can be overridden by creating your own templates and updating the config.
