<?php
	
	namespace Marwa\Application\Jobs;
	
	interface ListenerInterface {
		
		public function handle( array $params = [] ) : void;
	}
