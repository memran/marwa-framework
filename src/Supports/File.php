<?php

declare(strict_types=1);

namespace Marwa\Framework\Supports;

final class File
{
    public function __construct(private string $path) {}

    public static function path(string $path): self
    {
        return new self($path);
    }

    public function pathName(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function missing(): bool
    {
        return !$this->exists();
    }

    public function basename(): string
    {
        return basename($this->path);
    }

    public function name(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function mime(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        $mime = mime_content_type($this->path);

        return is_string($mime) ? $mime : null;
    }

    public function size(): int
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File [%s] does not exist.', $this->path));
        }

        $size = filesize($this->path);

        if (!is_int($size)) {
            throw new \RuntimeException(sprintf('Unable to determine file size for [%s].', $this->path));
        }

        return $size;
    }

    public function read(): string
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('File [%s] does not exist.', $this->path));
        }

        $contents = file_get_contents($this->path);

        if (!is_string($contents)) {
            throw new \RuntimeException(sprintf('Unable to read file [%s].', $this->path));
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function readJson(bool $associative = true): array
    {
        $decoded = json_decode($this->read(), $associative, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('JSON file [%s] did not decode to an array.', $this->path));
        }

        return $decoded;
    }

    public function write(string $contents): self
    {
        $this->ensureParentDirectory();

        $directory = dirname($this->path);
        $tempPath = tempnam($directory, 'marwa-file-');

        if (!is_string($tempPath)) {
            throw new \RuntimeException(sprintf('Unable to create a temporary file in [%s].', $directory));
        }

        try {
            $bytes = file_put_contents($tempPath, $contents, LOCK_EX);

            if (!is_int($bytes)) {
                throw new \RuntimeException(sprintf('Unable to write file [%s].', $this->path));
            }

            if (!rename($tempPath, $this->path)) {
                throw new \RuntimeException(sprintf('Unable to move temporary file into [%s].', $this->path));
            }
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }

        return $this;
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     */
    public function writeJson(array $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): self
    {
        return $this->write(json_encode($data, JSON_THROW_ON_ERROR | $flags));
    }

    public function append(string $contents): self
    {
        $this->ensureParentDirectory();
        $bytes = file_put_contents($this->path, $contents, FILE_APPEND | LOCK_EX);

        if (!is_int($bytes)) {
            throw new \RuntimeException(sprintf('Unable to append to file [%s].', $this->path));
        }

        return $this;
    }

    public function prepend(string $contents): self
    {
        return $this->write($contents . ($this->exists() ? $this->read() : ''));
    }

    public function copyTo(string $destination, bool $overwrite = false): self
    {
        if (!$this->exists()) {
            throw new \RuntimeException(sprintf('Source file [%s] does not exist.', $this->path));
        }

        $target = new self($destination);
        $target->ensureParentDirectory();

        if (!$overwrite && $target->exists()) {
            throw new \RuntimeException(sprintf('Destination file [%s] already exists.', $destination));
        }

        if (!copy($this->path, $destination)) {
            throw new \RuntimeException(sprintf('Unable to copy file [%s] to [%s].', $this->path, $destination));
        }

        return $target;
    }

    public function moveTo(string $destination, bool $overwrite = false): self
    {
        $target = new self($destination);
        $target->ensureParentDirectory();

        if (!$overwrite && $target->exists()) {
            throw new \RuntimeException(sprintf('Destination file [%s] already exists.', $destination));
        }

        if ($overwrite && $target->exists()) {
            $target->delete();
        }

        if (!rename($this->path, $destination)) {
            throw new \RuntimeException(sprintf('Unable to move file [%s] to [%s].', $this->path, $destination));
        }

        $this->path = $destination;

        return $this;
    }

    public function delete(): bool
    {
        if ($this->missing()) {
            return false;
        }

        return unlink($this->path);
    }

    public function ensureDirectory(): self
    {
        if (!is_dir($this->path) && !mkdir($this->path, 0755, true) && !is_dir($this->path)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $this->path));
        }

        return $this;
    }

    private function ensureParentDirectory(): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory [%s].', $directory));
        }
    }
}
