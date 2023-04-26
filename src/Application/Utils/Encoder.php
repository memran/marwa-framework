<?php
	declare( strict_types = 1 );
	
	
	namespace Marwa\Application\Utils;
	
	class Encoder {
		
		/**
		 * [gzipEncode description] string encode to gunzip
		 *
		 * @param string $content [description]
		 * @return string          [description]
		 */
		public static function gzipEncode( string $content ) : string
		{
			return gzencode($content);
		}
		
		/**
		 * [deflateEncode description] string encode to deflate
		 *
		 * @param string $content [description]
		 * @return string          [description]
		 */
		public static function deflateEncode( string $content ) : string
		{
			return gzdeflate($content);
		}
		
	}
