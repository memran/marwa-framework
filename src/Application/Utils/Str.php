<?php
namespace Marwa\Application\Utils;


class Str
{

	/**
	 * Generates a secure random string.
	 * @param int $length The length of the random string.
	 * @return string The generated random string.
	 * @throws \Exception If random bytes generation fails.
	 * @example $randomString = Str::random(16);
	 */
	public static function random(int $length = 32)
	{
		return bin2hex(random_bytes($length / 2)); // Generate a secure random string
	}
	/**
	 * Generates a secure random password.
	 * @param int $length The length of the password.
	 * @return string The generated password.
	 * @throws \Exception If random bytes generation fails.
	 * @example $password = Str::password(12);
	 */
	public static function password($length = 12) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}
		return $randomString; // Generate a secure random password	
	}
	/**
	 * Generates a slug from the given text.
	 * @param string $text The text to slugify.
	 * @param string $separator The separator to use (default is '-').
	 * @return string The slugified text.
	 * @example $slug = Str::slugify('Hello World!', '-');
	 */
	public static function slugify(string $text, $separator = '-')
	{
		// Replace non-letter or digits by separator
		$text = preg_replace('~[^\pL\d]+~u', $separator, $text);

		// Transliterate characters to ASCII
		$text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text);

		// Remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		// Trim the text
		$text = trim($text, $separator);

		return strtolower($text);
	}

}