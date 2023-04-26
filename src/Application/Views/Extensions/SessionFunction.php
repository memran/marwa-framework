<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class SessionFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param string $key
		 * @return string
		 */
		public function getSessionFunction( string $key )
		{
			return session($key);
		}
	}