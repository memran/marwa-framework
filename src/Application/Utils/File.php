<?php

namespace Marwa\Application\Utils;

use Nette\Utils\FileSystem as FS;

class File
{

	/**
	 * @param string $dirName
	 * @param int $mode
	 */
	public static function createDir(string $dirName, int $mode = 0777): void
	{
		FS::createDir($dirName, $mode);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param bool $overwrite
	 * @throws \Marwa\Application\Exceptions\FileNotFoundException
	 */
	public static function copy(string $source, string $destination, bool $overwrite = true): void
	{
		FS::copy($source, $destination, $overwrite);
	}

	/**
	 * @param string $file
	 * @param string $content
	 * @param int $mode
	 */
	public static function write(string $file, string $content, int $mode = 0666): void
	{
		FS::write($file, $content, $mode);
	}

	/**
	 * @param string $file
	 * @return string
	 * @throws \Marwa\Application\Exceptions\FileNotFoundException
	 */
	public static function read(string $file): string
	{
		return FS::read($file);
	}

	/**
	 * @param string $file
	 * @return string
	 * @throws \Marwa\Application\Exceptions\FileNotFoundException
	 */
	public static function readLines(string $file, bool $stripNewLines = true): string
	{
		return FS::readLines($file, $stripNewLines);
	}

	/**
	 * @param string $file
	 */
	public static function delete(string $file): void
	{
		FS::delete($file);
	}

	/**
	 * [isAbsolute description]
	 *
	 * @param string $path [description]
	 * @return boolean       [description]
	 */
	public static function isAbsolute(string $path): bool
	{
		return FS::isAbsolute($path);
	}

	/**
	 * @param string $filepath
	 * @return bool
	 */
	public static function has(string $filepath)
	{
		if (!empty($filepath)) {
			return file_exists($filepath);
		}

		return false;
	}
}