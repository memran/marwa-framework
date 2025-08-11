<?php

declare(strict_types=1);

namespace Marwa\App\Contracts;

use DateTimeInterface;

interface FilesystemInterface
{
    /**
     * Get the absolute path (inside the configured root) for a relative path.
     */
    public function path(string $path = ''): string;

    /**
     * Determine if a file or directory exists.
     */
    public function exists(string $path): bool;

    /**
     * Read entire file contents as string.
     * @throws \App\Support\FilesystemException if file missing or unreadable.
     */
    public function get(string $path): string;

    /**
     * Write contents to file (create or overwrite).
     * Returns number of bytes written.
     */
    public function put(string $path, string $contents): int;

    /**
     * Append contents to file. Creates file if missing.
     */
    public function append(string $path, string $contents): int;

    /**
     * Prepend contents to file. Creates file if missing.
     */
    public function prepend(string $path, string $contents): int;

    /**
     * Delete file(s) or directory(ies). Returns true if all deletions succeeded.
     */
    public function delete(string|array $paths): bool;

    /**
     * Copy file from $from to $to (overwrites if exists).
     */
    public function copy(string $from, string $to): void;

    /**
     * Move/Rename file from $from to $to (overwrites if exists).
     */
    public function move(string $from, string $to): void;

    /**
     * Create directory if not exists (recursive). Returns true if created, false if already existed.
     */
    public function makeDirectory(string $path, int $mode = 0755): bool;

    /**
     * Remove directory and all contents (if exists).
     */
    public function deleteDirectory(string $path): void;

    /**
     * Remove all files and folders inside directory, keeping the directory.
     */
    public function cleanDirectory(string $path): void;

    /**
     * Change permissions on a path.
     */
    public function chmod(string $path, int $mode): void;

    /**
     * Get file size in bytes.
     */
    public function size(string $path): int;

    /**
     * Get last modified unix timestamp.
     */
    public function lastModified(string $path): int;

    /**
     * List files in a directory (non-recursive).
     * @return string[] paths relative to root
     */
    public function files(string $directory = '', bool $hidden = false): array;

    /**
     * List files in a directory (recursive).
     * @return string[] paths relative to root
     */
    public function allFiles(string $directory = '', bool $hidden = false): array;

    /**
     * List directories in a directory (non-recursive).
     * @return string[] paths relative to root
     */
    public function directories(string $directory = ''): array;
}
