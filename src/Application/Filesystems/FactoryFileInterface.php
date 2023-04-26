<?php
	
	
	namespace Marwa\Application\Filesystems;
	
	use Marwa\Application\Filesystems\Adapters\AdapterInterface;
	
	interface FactoryFileInterface {
		
		public static function create( string $type ) : AdapterInterface;
	}
