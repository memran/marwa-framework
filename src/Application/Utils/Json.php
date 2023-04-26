<?php
	
	namespace Marwa\Application\Utils;
	
	class Json {
		
		/**
		 *
		 */
		public const FORCE_ARRAY = 0b0001;
		
		public const PRETTY = 0b0010;
		/**
		 *
		 */
		public const ESCAPE_UNICODE = 0b0100;
		
		/**
		 * Returns the JSON representation of a value. Accepts flag Json::PRETTY.
		 *
		 * @param mixed $value
		 * @param int $flags
		 * @return string
		 * @throws JsonException
		 */
		public static function encode( $value, int $flags = 0 ) : string
		{
			$flags = ( $flags & self::ESCAPE_UNICODE ? 0 : JSON_UNESCAPED_UNICODE )
				| JSON_UNESCAPED_SLASHES
				| ( $flags & self::PRETTY ? JSON_PRETTY_PRINT : 0 )
				| ( defined('JSON_PRESERVE_ZERO_FRACTION') ? JSON_PRESERVE_ZERO_FRACTION : 0 ); // since PHP 5.6.6 & PECL JSON-C 1.3.7
			
			$json = json_encode($value, $flags);
			if ( $error = json_last_error() )
			{
				throw new JsonException(json_last_error_msg(), $error);
			}
			
			return $json;
		}
		
		
		/**
		 * Decodes a JSON string. Accepts flag Json::FORCE_ARRAY.
		 *
		 * @param string $json
		 * @param int $flags
		 * @return mixed
		 * @throws JsonException
		 */
		public static function decode( string $json, int $flags = 0 )
		{
			$forceArray = (bool) ( $flags & self::FORCE_ARRAY );
			$value = json_decode($json, $forceArray, 512, JSON_BIGINT_AS_STRING);
			if ( $error = json_last_error() )
			{
				throw new JsonException(json_last_error_msg(), $error);
			}
			
			return $value;
		}
	}
