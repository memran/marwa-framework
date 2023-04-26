<?php
	declare( strict_types = 1 );
	
	namespace Marwa\Application\Views\Interfaces;
	
	interface ViewServiceInterface {
		
		public function raw( string $file, array $args = [] ) : string;
		
		public function render( string $file, array $args = [], int $status = 200, array $headers = [] );
		
		public function saveToCache( string $content );
		
		public function getFromCache();
		
		public function cache();
		
		public function expire( int $ttl );
		
		public function error404( array $args = [] );
		
		
	}
