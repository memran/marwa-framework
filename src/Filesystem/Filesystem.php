<?php

declare(strict_types=1);

namespace Marwa\App\Filesystem;

use League\Flysystem\Filesystem as FlyFS;
use League\Flysystem\StorageAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\Visibility;
use RuntimeException;

/**
 * Filesystem
 *
 * A thin, Laravel-style wrapper around Flysystem for developer ergonomics.
 * Immutable and thread-safe (no shared state), delegating to Flysystem internally.
 *
 * Methods return strict types and throw meaningful exceptions.
 */
final class Filesystem
{
    private FlyFS $fly;
    /** @var array<string,mixed> */
    private array $config;

    public function __construct(FlyFS $flysystem, array $config = [])
    {
        $this->fly = $flysystem;
        $this->config = $config;
    }

    /**
     * Write or overwrite a file with raw contents.
     */
    public function put(string $path, string $contents, string $visibility = Visibility::PUBLIC): void
    {
        $this->assertPath($path);
        if ($this->exists($path)) {
            $this->fly->delete($path);
        }
        $this->fly->write($path, $contents, ['visibility' => $visibility]);
    }

    /**
     * Write a file stream (caller should pass an open readable resource).
     */
    public function putStream(string $path, $resource, string $visibility = Visibility::PUBLIC): void
    {
        $this->assertPath($path);
        if (!is_resource($resource)) {
            throw new RuntimeException('putStream requires a valid stream resource.');
        }
        if ($this->exists($path)) {
            $this->fly->delete($path);
        }
        $this->fly->writeStream($path, $resource, ['visibility' => $visibility]);
    }

    /**
     * Read the entire file as string.
     */
    public function get(string $path): string
    {
        $this->assertPath($path);
        return $this->fly->read($path);
    }

    /**
     * Read a file as stream. Caller is responsible for closing the returned resource.
     * @return resource
     */
    public function readStream(string $path)
    {
        $this->assertPath($path);
        $stream = $this->fly->readStream($path);
        if (!is_resource($stream)) {
            throw new RuntimeException("Unable to read stream for [{$path}].");
        }
        return $stream;
    }

    public function delete(string $path): void
    {
        $this->assertPath($path);
        if ($this->exists($path)) {
            $this->fly->delete($path);
        }
    }

    public function copy(string $from, string $to): void
    {
        $this->assertPath($from);
        $this->assertPath($to);
        $this->fly->copy($from, $to);
    }

    public function move(string $from, string $to): void
    {
        $this->assertPath($from);
        $this->assertPath($to);
        $this->fly->move($from, $to);
    }

    public function exists(string $path): bool
    {
        $this->assertPath($path);
        return $this->fly->fileExists($path);
    }

    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    public function size(string $path): int
    {
        $this->assertPath($path);
        try {
            return $this->fly->fileSize($path);
        } catch (UnableToRetrieveMetadata $e) {
            throw new RuntimeException("Unable to retrieve size for [{$path}].", 0, $e);
        }
    }

    public function lastModified(string $path): int
    {
        $this->assertPath($path);
        try {
            return $this->fly->lastModified($path);
        } catch (UnableToRetrieveMetadata $e) {
            throw new RuntimeException("Unable to retrieve lastModified for [{$path}].", 0, $e);
        }
    }

    public function visibility(string $path): string
    {
        $this->assertPath($path);
        return $this->fly->visibility($path);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->assertPath($path);
        $this->fly->setVisibility($path, $visibility);
    }

    public function makeDirectory(string $path): void
    {
        $this->assertPath($path);
        if (!$this->fly->directoryExists($path)) {
            $this->fly->createDirectory($path);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $this->assertPath($path);
        if ($this->fly->directoryExists($path)) {
            $this->fly->deleteDirectory($path);
        }
    }

    /**
     * List files in a directory (non-recursive).
     * @return string[]
     */
    public function files(string $directory = ''): array
    {
        /** @var DirectoryListing $listing */
        $listing = $this->fly->listContents($directory, false);
        return array_values(array_map(
            static fn(StorageAttributes $attr) => $attr->path(),
            array_filter(iterator_to_array($listing), static fn(StorageAttributes $attr) => $attr->isFile())
        ));
    }

    /**
     * List files recursively.
     * @return string[]
     */
    public function allFiles(string $directory = ''): array
    {
        /** @var DirectoryListing $listing */
        $listing = $this->fly->listContents($directory, true);
        return array_values(array_map(
            static fn(StorageAttributes $attr) => $attr->path(),
            array_filter(iterator_to_array($listing), static fn(StorageAttributes $attr) => $attr->isFile())
        ));
    }

    /**
     * List directories (non-recursive).
     * @return string[]
     */
    public function directories(string $directory = ''): array
    {
        /** @var DirectoryListing $listing */
        $listing = $this->fly->listContents($directory, false);
        return array_values(array_map(
            static fn(StorageAttributes $attr) => $attr->path(),
            array_filter(iterator_to_array($listing), static fn(StorageAttributes $attr) => $attr->isDir())
        ));
    }

    /**
     * List directories recursively.
     * @return string[]
     */
    public function allDirectories(string $directory = ''): array
    {
        /** @var DirectoryListing $listing */
        $listing = $this->fly->listContents($directory, true);
        return array_values(array_map(
            static fn(StorageAttributes $attr) => $attr->path(),
            array_filter(iterator_to_array($listing), static fn(StorageAttributes $attr) => $attr->isDir())
        ));
    }

    /**
     * Build a public URL when supported via config (e.g., S3/CDN, or local base URL).
     * Returns null if not resolvable.
     */
    public function url(string $path): ?string
    {
        $this->assertPath($path);
        $base = $this->config['url'] ?? null;
        if (is_string($base) && $base !== '') {
            return rtrim($base, '/') . '/' . ltrim($path, '/');
        }
        return null;
    }

    /**
     * Driver-specific temporary URL (S3). Requires S3 config and credentials.
     * $expires is seconds from now (default 600s).
     */
    public function temporaryUrl(string $path, int $expires = 600): ?string
    {
        // Only S3 is supported here; others return null gracefully.
        if (($this->config['driver'] ?? null) !== 's3') {
            return null;
        }

        // If consumer passes a custom generator closure in config, prefer it.
        if (isset($this->config['temporary_url_resolver']) && is_callable($this->config['temporary_url_resolver'])) {
            /** @var callable $resolver */
            $resolver = $this->config['temporary_url_resolver'];
            return (string)$resolver($path, $expires, $this->config);
        }

        // Minimal generic attempt using AWS SDK if available.
        // We avoid tight coupling here to keep Filesystem simple.
        return null;
    }

    private function assertPath(string $path): void
    {
        if ($path === '' || preg_match('#\.\./#', $path) === 1) {
            throw new RuntimeException('Invalid path. Empty or path traversal is not allowed.');
        }
    }
}
