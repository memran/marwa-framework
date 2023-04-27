<?php
declare(strict_types=1);

namespace Marwa\Application;

use Marwa\Application\Utils\Filter;
use Marwa\Application\Utils\Validate;

class Input
{

	/**
	 * @param string $name
	 * @return string|null
	 * @throws Exceptions\FileNotFoundException
	 */
	public static function get(string $name)
	{
		return (new self())->parseQuery('GET', $name);
	}

	/**
	 * @param string $method
	 * @param string $name
	 * @return string|null
	 * @throws Exceptions\FileNotFoundException
	 */
	protected function parseQuery(string $method = 'GET', $name)
	{
		if (Validate::isNone($name)) {
			return null;
		}

		if ($method === 'GET') {
			$query = app('request')->getQueryParams();
		} else {
			$query = app('request')->getParsedBody();
		}

		if (!is_null($name) && array_key_exists($name, $query)) {
			if (is_string($query[$name])) {
				return Filter::escape($query[$name]);
			}

			return $query[$name];

		}

		return null;
	}

	/**
	 * @param string $name
	 * @return bool|string|null
	 * @throws Exceptions\FileNotFoundException
	 */
	public static function post(string $name)
	{
		return (new self())->parseQuery('POST', $name);
	}

	/**
	 * @param string $name
	 * @return bool|string|null
	 * @throws Exceptions\FileNotFoundException
	 */
	public static function any($name = '')
	{
		return (new self())->parseQuery(app('request')->getMethod(), $name);
	}

	/**
	 * @param string|null $file
	 * @return mixed
	 * @throws Exceptions\FileNotFoundException
	 */
	public static function files(string $file = null)
	{

		if (!is_null($file) && isset(app('request')->getUploadedFiles()[$file])) {
			return app('request')->getUploadedFiles()[$file];
		}

		return app('request')->getUploadedFiles();
	}

}