# Theme Guide

This guide covers creating and managing themes in the Marwa Framework.

## Overview

Themes provide a way to change the visual appearance of your application without modifying your application code. Each theme contains:

- Templates (Twig files)
- Assets (CSS, JS, images)
- Configuration (manifest.php)

## Quick Start

### Create a Theme

```bash
php marwa make:theme dark
```

### Create with Parent

```bash
php marwa make:theme dark --parent=default
```

## Theme Structure

```
resources/views/themes/
└── dark/
    ├── manifest.php        # Theme configuration
    ├── views/              # Template files
    │   ├── layout.twig
    │   └── home.twig
    └── assets/             # CSS, JS, images
        ├── css/
        │   └── app.css
        └── js/
            └── app.js
```

## Manifest Configuration

### Basic Manifest

```php
<?php
// resources/views/themes/dark/manifest.php

return [
    'name' => 'Dark Theme',
    'version' => '1.0.0',
    'description' => 'A dark-themed layout',
    'author' => 'Your Name',
    'parent' => null,  // No parent theme
];
```

### With Parent Theme

```php
<?php

return [
    'name' => 'Dark Theme',
    'version' => '1.0.0',
    'description' => 'Dark theme extending default',
    'author' => 'Your Name',
    'parent' => 'default',  // Extends default theme
];
```

### Manifest Options

| Option | Type | Description |
|--------|------|-------------|
| `name` | `string` | Theme display name |
| `version` | `string` | Theme version |
| `description` | `string` | Theme description |
| `author` | `string` | Theme author |
| `parent` | `string|null` | Parent theme name |
| `config` | `array` | Theme-specific config |

## Template Files

### Layout File

Create a base layout for your theme. Note: Use standard Twig syntax for templates.

```html
<!-- resources/views/themes/dark/views/layout.twig -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My App</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="theme-dark">
    <header>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
        </nav>
    </header>
    
    <main>Content goes here</main>
    
    <footer>
        <p>2024</p>
    </footer>
    
    <script src="js/app.js"></script>
</body>
</html>
```

### Child Page

Extend the layout in child templates - see Twig documentation for syntax.

## Assets

### Directory Structure

```
assets/
├── css/
│   └── app.css
├── js/
│   └── app.js
└── images/
    └── logo.png
```

### Using Assets in Templates

```html
<!-- CSS -->
<link rel="stylesheet" href="{{ theme_asset('css/app.css') }}">

<!-- JS -->
<script src="{{ theme_asset('js/app.js') }}"></script>

<!-- Image -->
<img src="{{ theme_asset('images/logo.png') }}" alt="Logo">
```

### Default Assets Helper

```html
<!-- Uses theme or fallback theme assets -->
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<img src="{{ asset('images/logo.png') }}">
```

## Theme Inheritance

### How It Works

When a theme has a parent:

1. Templates resolve in the following order:
   - Current theme views
   - Parent theme views
   - Fallback theme views

2. Assets resolve similarly:
   - Current theme assets
   - Parent theme assets

### Example: Overriding a Layout

Parent theme (`default`) - create base layout:

```html
<!-- default/views/layout.twig -->
<!DOCTYPE html>
<html>
<body>
    <header>Default Header</header>
    <main>Content goes here</main>
</body>
</html>
```

Child theme (`dark`) - only override what you need:

```html
<!-- dark/views/layout.twig -->
<!-- Extends parent layout, wraps content in div -->
<div class="dark-mode">Content</div>
```

### Result

The dark theme inherits the parent layout but wraps the content in a dark-mode div.

## Switching Themes

### At Runtime

```php
// Switch to a specific theme
view()->useTheme('dark');

// Set fallback theme
view()->setFallbackTheme('default');

// Get current theme
$current = view()->currentTheme();
```

### Based on User Preference

```php
// In controller or middleware
public function handle($request, $next)
{
    $user = auth()->user();
    
    if ($user && $user->theme_preference) {
        view()->useTheme($user->theme_preference);
    }
    
    return $next($request);
}
```

### Based on Time

```php
$hour = now()->hour;

if ($hour >= 18 || $hour < 6) {
    view()->useTheme('dark');
} else {
    view()->useTheme('light');
}
```

### Using Cache

```php
// Cache theme preference
$theme = cache('user.theme', function () {
    return auth()->user()->theme ?? 'default';
});

view()->useTheme($theme);
```

## Configuration

### config/view.php

```php
return [
    'paths' => [
        resource_path('views'),
    ],
    'cachePath' => storage_path('cache/views'),
    'debug' => env('VIEW_DEBUG', false),
    
    // Theme configuration
    'themePath' => resource_path('views/themes'),
    'activeTheme' => env('DEFAULT_THEME', 'default'),
    'fallbackTheme' => 'default',
];
```

### .env

```env
DEFAULT_THEME=default
```

## Creating Themes Programmatically

### Register Theme Path

```php
// In service provider
view()->addPath(resource_path('views/themes/custom'));
```

### Add Namespace

```php
view()->addNamespace('mytheme', resource_path('views/themes/mytheme'));
```

## Troubleshooting

### Theme Not Found

1. Check theme directory exists:
```
resources/views/themes/yourtheme/
```

2. Verify manifest.php exists:
```
resources/views/themes/yourtheme/manifest.php
```

3. Check file permissions:
```bash
chmod -R 775 resources/views/themes/
```

### Templates Not Found

1. Clear view cache:
```bash
php marwa cache:clear
# or
view()->clearCache();
```

2. Check template path in theme:
```
resources/views/themes/yourtheme/views/
```

### Assets Not Loading

1. Check asset directory:
```
resources/views/themes/yourtheme/assets/
```

2. Use correct asset helper:
```html
{{ theme_asset('css/app.css') }}
```

## Best Practices

### 1. Use Parent Themes

Always extend a parent theme unless you're creating a completely standalone theme:

```php
// In manifest.php
'parent' => 'default',
```

### 2. Override Only What You Need

Only create templates you want to customize:

```
dark/views/     # Only override these
- layout.twig   # Customize layout
- home.twig     # Customize home

# Other templates use parent's:
- about.twig    # Uses default/about.twig
- contact.twig # Uses default/contact.twig
```

### 3. Keep Assets Organized

```
assets/
├── css/
│   └── app.css      # Main styles
├── js/
│   └── app.js       # Main scripts
└── images/         # Theme images
```

### 4. Version Your Themes

```php
// In manifest.php
return [
    'name' => 'Dark Theme',
    'version' => '1.0.0',
    // ...
];
```

## Commands

| Command | Description |
|---------|-------------|
| `make:theme name` | Create new theme |
| `make:theme name --parent=default` | Create with parent |

## Related

- [View](view.md) - View rendering
- [Configuration](configuration.md) - Config options