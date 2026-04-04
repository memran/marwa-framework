# View

The framework ships a thin wrapper around `marwa-view` for rendering and theme switching.

## Render Views

```php
view()->share('appName', 'Marwa');

echo view()->render('welcome', [
    'name' => 'Alice',
]);
```

Use the helper shortcut when you want an HTML response:

```php
return view('welcome', ['name' => 'Alice']);
```

## Theme Management

Themes are discovered from `resources/views/themes/<theme>` by default. Each theme should contain a manifest file and a `views/` directory.

```php
view()->theme('dark');
view()->setFallbackTheme('default');
```

The wrapper falls back to the configured fallback theme when the requested theme is not available.
