# View API

API reference for `Marwa\Framework\Views\View`.

## Access

```php
$views = view();
$views->share('appName', 'Marwa');
```

## Render Methods

| Method | Description | Returns |
|-------|-------------|---------|
| `render(string $template, array $data)` | Render template to string | `string` |
| `make(string $template, array $data)` | Return HTML response | `ResponseInterface` |
| `exists(string $template)` | Check template exists | `bool` |

## Data Methods

| Method | Description |
|-------|-------------|
| `share(string $key, mixed $value)` | Share data globally |
| `shared(string $key)` | Get shared data |
| `flushShared()` | Clear shared data |

## Namespace Methods

| Method | Description |
|-------|-------------|
| `addNamespace(string $namespace, string $path)` | Register namespace |
| `addPath(string $path, ?string $namespace)` | Add template path |

## Theme Methods

| Method | Description |
|-------|-------------|
| `theme(?string $name)` | Get or change current theme |
| `useTheme(string $name)` | Switch active theme |
| `setFallbackTheme(string $name)` | Set fallback theme |
| `currentTheme()` | Get active theme name |
| `selectedTheme()` | Get selected theme name |
| `clearCache()` | Clear Twig cache |

## Shared View Helper

```php
// Render view
view('template', $data);

// Share data globally
view()->share('key', 'value');
```

## Configuration

In `config/view.php`:

```php
return [
    'paths' => [
        resource_path('views'),
    ],
    'cachePath' => storage_path('cache/views'),
    'debug' => env('VIEW_DEBUG', false),
    'themePath' => resource_path('views/themes'),
    'activeTheme' => 'default',
    'fallbackTheme' => null,
];
```

## Example Usage

```php
<?php

use Marwa\Framework\Views\View;

$view = new View($container);

// Render template
$html = $view->render('welcome', [
    'name' => 'Alice',
]);

// Make response
$response = $view->make('welcome', [
    'name' => 'Alice',
]);

// Share global data
$view->share('siteName', 'My App');

// Add namespace
$view->addNamespace('blog', resource_path('views/blog'));

// Theme
$view->useTheme('dark');
$view->clearCache();
```

## Related

- [View Tutorial](../tutorials/view.md) - Full tutorial
- [Twig Documentation](https://twig.symfony.com/)