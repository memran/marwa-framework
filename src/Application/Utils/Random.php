<?php

	namespace Marwa\Application\Utils;
	
	use Nette\Utils\Random as RD;
	
	class Random {
		
		public static function generate( int $length = 10, string $charlist = '0-9a-zA-Z' ) : string
		{
			return RD::generate($length, $charlist);
		}
	}
