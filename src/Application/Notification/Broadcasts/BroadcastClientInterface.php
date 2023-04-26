<?php
	
	namespace Marwa\Application\Notification\Broadcasts;
	
	interface BroadcastClientInterface {
		
		public function process( BroadcastBuilder $v );
		
	}
