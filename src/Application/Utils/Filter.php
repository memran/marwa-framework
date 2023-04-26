<?php
	declare( strict_types = 1 );
	
	namespace Marwa\Application\Utils;
	
	class Filter {
		
		/**
		 * Check if a string is a valid timezone
		 *
		 * timezone_identifiers_list() requires PHP >= 5.2
		 *
		 * @param string $timezone
		 * @return bool
		 **/
		public static function isValidTimezone( string $timezone ) : bool
		{
			return in_array($timezone, timezone_identifiers_list());
		}
		
		/**
		 * @param string $str
		 * @return string
		 */
		public static function escape( string $str )
		{
			$str = str_replace('"', '', trim($str));
			
			return trim(str_replace("'", '', $str));
		}
		
		/**
		 * @param string $email
		 * @return bool
		 */
		public static function validEmail( string $email ) : bool
		{
			// Validate e-mail
			if ( !filter_var(static::sanitize($email), FILTER_VALIDATE_EMAIL) === false )
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * @param string $str
		 * @return string
		 */
		public static function sanitize( string $str ) : string
		{
			return filter_var($str, FILTER_SANITIZE_URL);
		}
		
		/**
		 * [validateIP description] validate ip format
		 *
		 * @param string $ip [description]
		 * @return bool     [description]
		 */
		public static function validIp( string $ip ) : bool
		{
			if ( !filter_var($ip, FILTER_VALIDATE_IP) === false )
			{
				return true;
			}
			
			return false;
			
		}
		
		/**
		 * [urlValidate description] valided url
		 *
		 * @param string $url [description]
		 * @return bool      [description]
		 */
		public static function validUrl( string $url ) : bool
		{
			// Remove all illegal characters from a url
			// Validate url
			if ( filter_var(static::sanitize($url), FILTER_VALIDATE_URL) )
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * [htmlSplChar description] htmlspecialchars
		 *
		 * @param string $url [description]
		 * @return string      [description]
		 */
		public static function htmlSplChar( string $url ) : string
		{
			return htmlspecialchars($url);
		}
	}
