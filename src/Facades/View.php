<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Views\View as FrameworkView;
use Psr\Http\Message\ResponseInterface;

/**
 * @method static ResponseInterface make(string $template, array<string, mixed> $data = [])
 * @method static string render(string $template, array<string, mixed> $data = [])
 * @method static bool exists(string $template)
 * @method static void share(string $key, mixed $value)
 * @method static void addNamespace(string $namespace, string $path)
 * @method static self theme(?string $name = null)
 * @method static void useTheme(string $name)
 * @method static void setFallbackTheme(string $name)
 * @method static string currentTheme()
 * @method static string selectedTheme()
 * @method static void clearCache()
 */
final class View extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FrameworkView::class;
    }
}
