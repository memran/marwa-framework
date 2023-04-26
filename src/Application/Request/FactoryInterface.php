<?php
	
	
	namespace Marwa\Application\Request;
	
	interface FactoryInterface {
		
		public static function create( string $type ) : Psr7Interface;
	}
