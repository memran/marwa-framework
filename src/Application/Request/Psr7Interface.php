<?php
	
	
	namespace Marwa\Application\Request;
	
	use Psr\Http\Message\RequestInterface;
	
	interface Psr7Interface {
		
		public function getRequest() : RequestInterface;
	}
