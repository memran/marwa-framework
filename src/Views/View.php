<?php

declare(strict_types=1);

namespace Marwa\Framework\Views;

use Marwa\Framework\Adapters\ViewAdapter;
use Marwa\Router\Response;
use Marwa\Support\Str;
use Psr\Http\Message\ResponseInterface;

final class View
{
    private string $fallbackTheme;

    public function __construct(private ViewAdapter $adapter)
    {
        $this->fallbackTheme = $this->adapter->selectedTheme() ?? 'default';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function make(string $template, array $data = []): ResponseInterface
    {
        return Response::html($this->render($template, $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        return $this->adapter->engine()->render($this->normalizeTemplateName($template), $data);
    }

    public function exists(string $template): bool
    {
        return $this->adapter->exists($this->normalizeTemplateName($template));
    }

    public function share(string $key, mixed $value): void
    {
        $this->adapter->share($key, $value);
    }

    public function addNamespace(string $namespace, string $path): void
    {
        $this->adapter->addNamespace($namespace, $path);
    }

    public function theme(?string $name = null): self|string
    {
        if ($name === null) {
            return $this->currentTheme();
        }

        $this->useTheme($name);

        return $this;
    }

    public function useTheme(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            return;
        }

        try {
            $this->adapter->useTheme($name);
            return;
        } catch (\Throwable) {
            if ($this->fallbackTheme !== '' && $this->fallbackTheme !== $name) {
                try {
                    $this->adapter->useTheme($this->fallbackTheme);
                } catch (\Throwable) {
                    // Keep the current engine state if the fallback theme is also unavailable.
                }
            }
        }
    }

    public function setFallbackTheme(string $name): void
    {
        $name = trim($name);

        if ($name !== '') {
            $this->fallbackTheme = $name;
        }
    }

    public function currentTheme(): string
    {
        return $this->adapter->currentTheme() ?? $this->fallbackTheme;
    }

    public function selectedTheme(): string
    {
        return $this->adapter->selectedTheme() ?? $this->fallbackTheme;
    }

    public function clearCache(): void
    {
        $this->adapter->engine()->clearCache();
    }

    public function raw(): ViewAdapter
    {
        return $this->adapter;
    }

    private function normalizeTemplateName(string $name): string
    {
        $name = trim(str_replace('\\', '/', $name));

        if ($name === '' || Str::contains($name, "\0")) {
            throw new \InvalidArgumentException('Template name cannot be empty or contain null bytes.');
        }

        $segments = [];
        foreach (explode('/', $name) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw new \InvalidArgumentException("Invalid template path '{$name}'");
            }

            $segments[] = $segment;
        }

        if ($segments === []) {
            throw new \InvalidArgumentException("Invalid template path '{$name}'");
        }

        $normalized = implode('/', $segments);

        if (!Str::endsWith($normalized, '.twig')) {
            $normalized .= '.twig';
        }

        return $normalized;
    }
}
