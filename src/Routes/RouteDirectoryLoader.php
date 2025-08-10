<?php

declare(strict_types=1);

namespace Marwa\App\Routes;

/**
 * Loads route definition files from a single directory (non-recursive).
 */
final class RouteDirectoryLoader
{
    /** @var string */
    private string $baseDir;

    /** @var string[] */
    private array $includeExtensions;

    /**
     * @param string   $baseDir           Absolute path to routes folder
     * @param string[] $includeExtensions Allowed extensions (default: ['php'])
     */
    public function __construct(string $baseDir, array $includeExtensions = ['php'])
    {
        $this->baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
        $this->includeExtensions = $includeExtensions;
    }

    /**
     * Load all matching files from baseDir.
     *
     * @return int Number of loaded files
     */
    public function load(): int
    {
        if (!is_dir($this->baseDir)) {
            return 0;
        }

        $files = scandir($this->baseDir);
        if ($files === false) {
            return 0;
        }

        $count = 0;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path)) {
                continue;
            }
            if (!$this->hasAllowedExtension($file)) {
                continue;
            }
            require $path;
            $count++;
        }
        return $count;
    }

    /**
     * Check if file has allowed extension.
     */
    private function hasAllowedExtension(string $filename): bool
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array(strtolower($ext), $this->includeExtensions, true);
    }
}
