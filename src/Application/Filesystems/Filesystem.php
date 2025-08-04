<?php declare(strict_types=1);
namespace Application\Filesystems;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path;

class Filesystem
{
    private SymfonyFilesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new SymfonyFilesystem();
    }

    /**
     * Checks if a file or directory exists at the specified path.
     * @param string $path The path to check.
     * @return bool Returns true if the file or directory exists, false otherwise.
     */
    public function exists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    /**
     * Removes a file or directory at the specified path.
     * @param string $path The path to remove.
     * @throws \RuntimeException If the file or directory could not be removed.
     * @return void
     */
    public function remove(string $path): void
    {
        try {
            $this->filesystem->remove($path);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not remove path "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Copies a file from the source path to the destination path.
     * @param string $source The path of the source file.
     * @param string $destination The path where the file should be copied.
     * @throws \RuntimeException If the file could not be copied.
     * @return void
     */
    public function copy(string $source, string $destination): void
    {
        try {
            $this->filesystem->copy($source, $destination);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not copy from "%s" to "%s": %s', $source, $destination, $exception->getMessage()));
        }
    }
    /**
     * Creates a directory at the specified path.
     * @param string $path The path where the directory should be created.
     * @param int $mode The permissions for the new directory (default is 0777).
     * @throws \RuntimeException If the directory could not be created.
     * @return void
     */
    public function mkdir(string $path, int $mode=0777): void
    {
        try {
            $this->filesystem->mkdir($path, $mode);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not create directory "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Returns the real path of a given path, resolving any symbolic links and relative paths.
     * @param string $path The path to resolve.
     * @return string The resolved real path.
     */
    public function getRealPath(string $path): string
    {
        return Path::canonicalize($path);
    }
    /**
     * Returns the relative path from a base path to a given path.
     * @param string $path The path to convert to a relative path.
     * @param string $basePath The base path from which the relative path is calculated.
     * @return string The relative path.
     */
    public function getRelativePath(string $path, string $basePath): string
    {
        return Path::makeRelative($path, $basePath);
    }
    /**
     * Touches a file at the specified path, updating its access and modification times.
     * If the file does not exist, it will be created.
     * @param string $path The path to touch.
     * @throws \RuntimeException If the file could not be touched.
     * @return void
     */
    public function touch(string $path): void
    {
        try {
            $this->filesystem->touch($path);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not touch path "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Returns the permissions of a file or directory at the specified path.
     * @param string $path The path of the file or directory.
     * @throws \RuntimeException If the permissions could not be retrieved.
     * @return int The permissions as an octal integer (e.g., 0755).
     */
    public function owner(string $path): string
    {
        try {
            return $this->filesystem->getOwner($path);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not get owner of path "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Returns the group of a file or directory at the specified path.
     * @param string $path The path of the file or directory.
     * @throws \RuntimeException If the group could not be retrieved.
     * @return string The name of the group.
     */
    public function group(string $path): string
    {
        try {
            return $this->filesystem->getGroup($path);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not get group of path "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Changes the permissions of a file or directory at the specified path.
     * @param string $path The path of the file or directory.
     * @param int $mode The permissions to set (e.g., 0755).
     * @throws \RuntimeException If the permissions could not be changed.
     * @return void
     */
    public function changePermissions(string $path, int $mode): void
    {
        try {
            $this->filesystem->chmod($path, $mode);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not change permissions of path "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Changes the owner of a file or directory at the specified path.
     * @param string $path The path of the file or directory.
     * @param string $user The name of the user to set as the owner.
     * @throws \RuntimeException If the owner could not be changed.
     * @return void
     */
    public function changeOwner(string $path, string $user): void
    {
        try {
            $this->filesystem->chown($path, $user);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not change owner of path "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Changes the group of a file or directory at the specified path.
     * @param string $path The path of the file or directory.
     * @param string $group The name of the group to set.
     * @throws \RuntimeException If the group could not be changed.
     * @return void
     */
    public function changeGroup(string $path, string $group): void
    {
        try {
            $this->filesystem->chgrp($path, $group);
        } catch (IOExceptionInterface $exception) {         
            throw new \RuntimeException(sprintf('Could not change group of path "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Renames a file or directory from the old name to the new name.
     * @param string $oldName The current name of the file or directory.
     * @param string $newName The new name for the file or directory.
     * @throws \RuntimeException If the rename operation fails.
     * @return void
     */
    public function rename(string $oldName, string $newName): void
    {
        try {
            $this->filesystem->rename($oldName, $newName);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not rename "%s" to "%s": %s', $oldName, $newName, $exception->getMessage()));
        }
    }
    /**
     * Creates a symbolic link from the target to the link.
     * @param string $target The target file or directory to link to.
     * @param string $link The name of the symbolic link to create.
     * @throws \RuntimeException If the symlink could not be created.
     * @return void
     */
    public function softlink(string $target, string $link): void
    {
        try {
            $this->filesystem->symlink($target, $link);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not create symlink from "%s" to "%s": %s', $target, $link, $exception->getMessage()));
        }
    }
    /**
     * Creates a hard link from the target to the link.
     * @param string $target The target file or directory to link to.
     * @param string $link The name of the hard link to create.
     * @throws \RuntimeException If the hard link could not be created.
     * @return void
     */
    public function hardlink(string $target, string $link): void
    {
        try {
            $this->filesystem->hardlink($target, $link);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not create hard link from "%s" to "%s": %s', $target, $link, $exception->getMessage()));
        }
    }
    /**
     * Reads the target of a symbolic link.
     * @param string $target The symbolic link to read.
     * @throws \RuntimeException If the link could not be read.
     * @return string|null The target of the symbolic link, or null if it does not exist.
     */
    public function link(string $target): ?string
    {
        try {
            $this->filesystem->readlink($target);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not create link for target "%s": %s', $target, $exception->getMessage()));
        }
    }

    public function mirror(string $source, string $destination, ?callable $filter = null): void
    {
        try {
            $this->filesystem->mirror($source, $destination, $filter);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not mirror from "%s" to "%s": %s', $source, $destination, $exception->getMessage()));
        }
    }
    /**
     * Checks if the given path is an absolute path.
     * @param string $path The path to check.
     * @return bool Returns true if the path is absolute, false otherwise.
     */
    public function isAbsolutePath(string $path): bool
    {
        return $this->filesystem->isAbsolute($path);
    }
    /**
     * Moves a file or directory from the source path to the destination path.
     * @param string $source The path of the source file or directory.
     * @param string $destination The path where the file or directory should be moved.
     * @throws \RuntimeException If the move operation fails.
     * @return void
     */
    public function move(string $source, string $destination): void
    {
        try {
            $this->filesystem->rename($source, $destination);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not move from "%s" to "%s": %s', $source, $destination, $exception->getMessage()));
        }
    }
    /**
     * Checks if the given path is a directory.
     * @param string $path The path to check.
     * @return bool Returns true if the path is a directory, false otherwise.
     */
    public function isDirectory(string $path): bool
    {
        return $this->filesystem->isDirectory($path);
    }
    /**
     * Checks if the given path is a file.
     * @param string $path The path to check.
     * @return bool Returns true if the path is a file, false otherwise.
     */
    public function isFile(string $path): bool
    {
        return $this->filesystem->isFile($path);
    }
    /**
     * Reads the content of a file at the specified path.
     * @param string $path The path of the file to read.
     * @throws \RuntimeException If the file could not be read.
     * @return string The content of the file.
     */
    public function read(string $path): string
    {
        try {
            return $this->filesystem->readFile($path);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not read file "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Writes content to a file at the specified path, creating the file if it does not exist.
     * @param string $path The path where the file should be written.
     * @param string $content The content to write to the file.
     * @throws \RuntimeException If the file could not be written.
     * @return void
     */
    public function write(string $path, string $content): void
    {
        try {
            $this->filesystem->dumpFile($path, $content);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not write to file "%s": %s', $path, $exception->getMessage()));
        }
    }
    public function append(string $path, string $content): void
    {
        try {
            $this->filesystem->appendToFile($path, $content);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not append to file "%s": %s', $path, $exception->getMessage()));
        }
    }
    /**
     * Returns the underlying Symfony Filesystem instance.
     * @return SymfonyFilesystem The Symfony Filesystem instance.
     */
    public function getFilesystem(): SymfonyFilesystem
    {
        return $this->filesystem;
    }
}