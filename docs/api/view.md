# View API

`Marwa\Framework\Views\View` wraps the shared `marwa-view` adapter.

## Access

```php
$views = view();
$views->share('appName', 'Marwa');
```

## Methods

- `render(string $template, array $data = []): string` renders a template to a string
- `make(string $template, array $data = []): ResponseInterface` returns an HTML response
- `exists(string $template): bool` checks whether a template is available
- `share(string $key, mixed $value): void` shares data globally
- `addNamespace(string $namespace, string $path): void` registers module or package namespaces
- `theme(?string $name = null): self|string` gets or changes the current theme
- `useTheme(string $name): void` switches the active theme
- `setFallbackTheme(string $name): void` changes the fallback theme
- `currentTheme(): ?string` returns the active theme name
- `selectedTheme(): ?string` returns the selected theme name
- `clearCache(): void` clears compiled Twig and fragment cache
