<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class __Filter implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param $word
		 * @return string
		 * @throws \Marwa\Application\Exceptions\FileNotFoundException
		 */
		public function get__Filter( $word )
		{
			return lang($word);
		}
	}