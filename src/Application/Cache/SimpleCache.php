<?php
	
	
	namespace Marwa\Application\Cache;
	
	use MatthiasMullie\Scrapbook\Psr16\SimpleCache as SC;
	
	
	class SimpleCache {
		
		public static function getInstance( $cache )
		{
			return new SC($cache);
		}
	}
