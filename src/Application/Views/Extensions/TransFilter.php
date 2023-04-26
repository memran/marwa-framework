<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class TransFilter implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param mixed $word
		 * @return object|string
		 */
		public function getTransFilter( $word )
		{
			return lang($word);
		}
	}