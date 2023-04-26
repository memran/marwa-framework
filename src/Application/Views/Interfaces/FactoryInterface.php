<?php
	
	
	namespace Marwa\Application\Views\Interfaces;
	
	
	interface FactoryInterface {
		
		public static function create( string $type, array $config ) : ViewServiceInterface;
	}
