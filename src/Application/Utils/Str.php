<?php
namespace Marwa\Application\Utils;

use Nette\Utils\Strings as NStrings;

class Str extends NStrings
{

	/**
	 * @param string $search
	 * @param array $searchTerm
	 * @return bool
	 */
	public static function containsArray(string $search, array $searchTerm)
	{
		$result = false;
		foreach ($searchTerm as $k) {
			$result = self::contains($search, self::upper($k));
			if ($result === true) {
				return $result;
			}
		}

		return $result;
	}

	public static function random(int $n = 16): string
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';

		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		return $randomString;
	}
}