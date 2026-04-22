# View

The framework wraps `marwa-view` for rendering Twig templates with theme support.

## Quick Example

### Render a View

```php
// Controller
return view('welcome', ['name' => 'Alice']);
```

### Using Helper

```php
view()->share('appName', 'Marwa');

echo view()->render('welcome', [
    'name' => 'Alice',
]);
```

## Template Basics

### Variables

In Twig templates, use double curly braces for variables:

```html
<!-- welcome.twig -->
<p>Hello, Alice!</p>
```

### Passing Data

```php
// From controller
return view('user.profile', [
    'user' => $user,
    'title' => 'Profile',
]);

// Or share globally
view()->share('siteName', 'My App');
```

### Using Global Data

```php
// Available in all views
view()->share('siteName', 'My App');
```

## Template Inheritance

Learn more about Twig template inheritance at: https://twig.symfony.com/doc/templates.html#inheritance

### Layout File

Create a base layout:

```html
<!-- resources/views/layout.twig -->
<!DOCTYPE html>
<html>
<head>
    <title>Default Title</title>
</head>
<body>
    <main>Content goes here</main>
</body>
</html>
```

### Child Template

Extend the layout in child templates:

```html
<!-- resources/views/home.twig -->
<!-- extends layout -->
<h1>Welcome</h1>
```

## Control Structures

For Twig control structures, see: https://twig.symfony.com/doc/templates.html

### If/Else Conditions

Use conditional logic in templates:

```html
<!-- If user exists -->
<p>Hello!</p>
```

### For Loops

Iterate over collections:

```html
<ul>
<li>Item 1</li>
<li>Item 2</li>
</ul>
```

### Filters

Apply Twig filters to modify output - see: https://twig.symfony.com/doc/filters/index.html

```html
<p>UPPERCASE TEXT</p>
<p>Truncated text...</p>
```

## Include Templates

For includes, see: https://twig.symfony.com/doc/tags/include.html

```html
<!-- Include header partial -->
<!-- Include navbar partial -->
```

## Macros

Create reusable components - see: https://twig.symfony.com/doc/tags/macro.html

```html
<!-- Define input field macro -->
<!-- Define button macro -->
```

## Namespaces

### Add View Namespace

```php
// In service provider or module
view()->addNamespace('blog', resource_path('views/themes/blog'));
```

For namespace usage in templates, use the double-colon syntax:

```html
<!-- blog::header -->
<!-- blog::layout -->
```

## Themes

### Theme Structure

```
resources/views/themes/
└── default/
    ├── manifest.php
    └── views/
        ├── layout.twig
        └── home.twig
```

### Manifest

```php
<?php
// resources/views/themes/default/manifest.php
return [
    'name' => 'Default',
    'parent' => null,
];
```

### Switch Theme

```php
view()->theme('dark');
view()->setFallbackTheme('default');
```

### Get Theme

```php
$current = view()->currentTheme();
$selected = view()->selectedTheme();
```

## Response Helpers

### HTML Response

```php
// Shortcut
return view('page', $data);

// With status
return response()->view('page', $data, 200);

// With headers
return response()->view('page', $data)
    ->header('X-Custom', 'value');
```

### View without Response

```php
// Render to string
$html = view()->render('page', $data);
```

## Cache

### Clear Twig Cache

```php
view()->clearCache();
```

If you disable compiled view caching after it has already been used, run a
cache clear once to remove any stale compiled templates that were written
before the change:

```bash
php marwa cache:clear
```

### Configuration

In `config/view.php`:

```php
return [
    'paths' => [
        resource_path('views'),
    ],
    'cachePath' => storage_path('cache/views'),
    'debug' => env('VIEW_DEBUG', false),
];
```

## Useful Twig Functions

### Asset URLs

```html
<link rel="stylesheet" href="css/style.css">
<script src="js/app.js"></script>
```

### Route URLs

```html
<a href="/">Home</a>
<a href="/user/1">Profile</a>
```

### Session Data

```html
<p>Hello, Guest</p>
```

### Config Values

```html
<p>App: My App</p>
```

## Complete Example

### Controller

```php
public function index(): Response
{
    view()->share('siteName', 'My Blog');
    
    return view('blog.index', [
        'posts' => Post::published(),
    ]);
}
```

### Layout

```html
<!-- resources/views/layout.twig -->
<!DOCTYPE html>
<html>
<head>
    <title>My Blog</title>
</head>
<body>
    <header>My Blog</header>
    <main>Content</main>
    <footer>2024</footer>
</body>
</html>
```

### Template

```html
<!-- resources/views/blog/index.twig -->
<h1>Latest Posts</h1>
<!-- Post list -->
```

## Helper Functions

### Global `view()` Helper

```php
// Render view
view('template', $data);

// Share data globally
view()->share('key', $value);

// Check exists
view()->exists('template');
```

## Configuration Reference

### config/view.php

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `paths` | `array` | `[]` | View directories |
| `cachePath` | `string` | - | Compiled views cache |
| `debug` | `bool` | `false` | Debug mode |
| `themePath` | `string` | - | Theme directory |
| `activeTheme` | `string` | `default` | Active theme |
| `fallbackTheme` | `string` | - | Fallback theme |

## Related

- [Controllers](controllers.md) - Using controllers
- [Models](models.md) - Using models
- [Validation](validation.md) - Form validation
