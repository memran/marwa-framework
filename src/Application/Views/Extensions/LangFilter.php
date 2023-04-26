<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class LangFilter implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param mixed $word
		 * @return object|string
		 */
		public function getLangFilter( $word )
		{
			return lang($word);
		}
	}