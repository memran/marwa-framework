<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Supports\Config as ConfigClass;

/**
 * @method static void load(string $filePath)
 * @method static bool loadIfExists(string $filePath)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static string getString(string $key, ?string $default = null)
 * @method static int getInt(string $key, ?int $default = null)
 * @method static bool getBool(string $key, ?bool $default = null)
 * @method static array<mixed> getArray(string $key, array<mixed>|null $default = null)
 */
final class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {

        return ConfigClass::class;
    }
}
