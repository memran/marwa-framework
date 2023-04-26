<?php
	
	
	namespace Marwa\Application\Cache\Builders;
	
	
	interface BuilderInterface {
		
		public function getAdapter();
		
		public function getType() : string;
	}
