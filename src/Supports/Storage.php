<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use Marwa\Framework\Application;
use Marwa\Framework\Config\StorageConfig;

final class Storage
{
    /**
     * @var array{
     *     default: string,
     *     disks: array<string, array<string, mixed>>
     * }|null
     */
    private ?array $storageConfig = null;

    /**
     * @var array<string, FilesystemOperator>
     */
    private array $resolvedDisks = [];

    public function __construct(
        private Application $app,
        private Config $config,
        private ?string $activeDisk = null
    ) {}

    public function disk(?string $name = null): self
    {
        $clone = clone $this;
        $clone->activeDisk = $name ?? $this->configuration()['default'];

        return $clone;
    }

    public function path(string $path = ''): string
    {
        $root = $this->diskConfig()['root'] ?? '';

        return rtrim($root . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''), DIRECTORY_SEPARATOR);
    }

    public function exists(string $path): bool
    {
        $this->validatePath($path);

        return $this->filesystem()->has($path);
    }

    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    public function read(string $path): string
    {
        $this->validatePath($path);

        return $this->filesystem()->read($path);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function readJson(string $path, bool $associative = true): array
    {
        $decoded = json_decode($this->read($path), $associative, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('JSON file [%s] did not decode to an array.', $path));
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function write(string $path, string $contents, array $config = []): bool
    {
        $this->filesystem()->write($path, $contents, $config);

        return true;
    }

    /**
     * @param array<string, mixed>|list<mixed> $contents
     */
    public function writeJson(string $path, array $contents, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): bool
    {
        return $this->write($path, json_encode($contents, JSON_THROW_ON_ERROR | $flags));
    }

    /**
     * @param resource $stream
     */
    /**
     * @param resource $stream
     * @param array<string, mixed> $config
     */
    public function writeStream(string $path, $stream, array $config = []): bool
    {
        $this->filesystem()->writeStream($path, $stream, $config);

        return true;
    }

    public function delete(string $path): bool
    {
        $this->validatePath($path);
        $this->filesystem()->delete($path);

        return true;
    }

    public function deleteDirectory(string $path): bool
    {
        $this->filesystem()->deleteDirectory($path);

        return true;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function makeDirectory(string $path, array $config = []): bool
    {
        $this->filesystem()->createDirectory($path, $config);

        return true;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function copy(string $source, string $destination, array $config = []): bool
    {
        $this->validatePath($source);
        $this->validatePath($destination);
        $this->filesystem()->copy($source, $destination, $config);

        return true;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function move(string $source, string $destination, array $config = []): bool
    {
        $this->validatePath($source);
        $this->validatePath($destination);
        $this->filesystem()->move($source, $destination, $config);

        return true;
    }

    public function size(string $path): int
    {
        $this->validatePath($path);

        return $this->filesystem()->fileSize($path);
    }

    public function mimeType(string $path): string
    {
        $this->validatePath($path);

        return $this->filesystem()->mimeType($path);
    }

    public function lastModified(string $path): int
    {
        $this->validatePath($path);

        return $this->filesystem()->lastModified($path);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function checksum(string $path, array $config = []): string
    {
        $this->validatePath($path);

        return $this->filesystem()->checksum($path, $config);
    }

    /**
     * @return list<string>
     */
    public function files(string $directory = '', bool $deep = false): array
    {
        return $this->extractPaths($this->filesystem()->listContents($directory, $deep), FileAttributes::class);
    }

    /**
     * @return list<string>
     */
    public function directories(string $directory = '', bool $deep = false): array
    {
        return $this->extractPaths($this->filesystem()->listContents($directory, $deep), DirectoryAttributes::class);
    }

    public function filesystem(): FilesystemOperator
    {
        $disk = $this->activeDisk ?? $this->configuration()['default'];

        if (isset($this->resolvedDisks[$disk])) {
            return $this->resolvedDisks[$disk];
        }

        $config = $this->diskConfig($disk);
        $driver = strtolower((string) ($config['driver'] ?? 'local'));

        $filesystem = match ($driver) {
            'local' => $this->localFilesystem($config),
            default => throw new \InvalidArgumentException(sprintf('Storage driver [%s] is not supported.', $driver)),
        };

        $this->resolvedDisks[$disk] = $filesystem;

        return $filesystem;
    }

    /**
     * @return array{
     *     default: string,
     *     disks: array<string, array<string, mixed>>
     * }
     */
    public function configuration(): array
    {
        if ($this->storageConfig !== null) {
            return $this->storageConfig;
        }

        $this->config->loadIfExists(StorageConfig::KEY . '.php');
        $this->storageConfig = StorageConfig::merge($this->app, $this->config->getArray(StorageConfig::KEY, []));

        return $this->storageConfig;
    }

    /**
     * @return array<string, mixed>
     */
    private function diskConfig(?string $disk = null): array
    {
        $name = $disk ?? $this->activeDisk ?? $this->configuration()['default'];
        $disks = $this->configuration()['disks'];

        if (!isset($disks[$name])) {
            throw new \InvalidArgumentException(sprintf('Storage disk [%s] is not configured.', $name));
        }

        return $disks[$name];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function localFilesystem(array $config): FilesystemOperator
    {
        $root = (string) ($config['root'] ?? '');

        if ($root === '') {
            throw new \InvalidArgumentException('Local storage disks require a root path.');
        }

        $adapter = new LocalFilesystemAdapter($root);

        return new Filesystem($adapter, [
            'visibility' => (string) ($config['visibility'] ?? 'private'),
        ]);
    }

    private function validatePath(string $path): void
    {
        $normalizedPath = '/' . ltrim(str_replace(['\\', '..'], ['/', ''], $path), '/');

        if (str_contains($normalizedPath, '../') || str_contains($normalizedPath, '/..')) {
            throw new \RuntimeException(sprintf('Path traversal attempt detected in path [%s].', $path));
        }
    }

    /**
     * @param iterable<StorageAttributes> $listing
     * @return list<string>
     */
    private function extractPaths(iterable $listing, string $type): array
    {
        $paths = [];

        foreach ($listing as $item) {
            if (!$item instanceof $type) {
                continue;
            }

            $paths[] = $item->path();
        }

        sort($paths, SORT_STRING);

        return $paths;
    }
}
