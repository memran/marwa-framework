<?php
	declare( strict_types = 1 );
	
	
	namespace Marwa\Application\Sessions;
	
	interface SessionServiceInterface {
		
		public function storage( string $name );
		
		public function expire( int $ttl );
		
		public function csrfTokenValue();
		
		public function regenerate();
		
		public function destroy();
	}
