<?php
	
	
	namespace Marwa\Application\Cache;
	
	interface FactoryInterface {
		
		public static function create( string $type );
	}
