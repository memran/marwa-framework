<?php
	
	
	namespace Marwa\Application\Request;
	
	class RequestFactory implements FactoryInterface {
		
		/**
		 * @param string $type
		 * @return Psr7Interface
		 */
		public static function create( string $type ) : Psr7Interface
		{
			return new Psr7Request();
		}
	}
