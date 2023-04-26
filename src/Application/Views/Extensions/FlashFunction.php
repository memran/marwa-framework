<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class FlashFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param string $key
		 * @return array
		 */
		public function getFlashFunction( $key )
		{
			return getMessage($key);
		}
	}