<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class Csrf_TokenFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @return string
		 */
		public function getCsrf_TokenFunction()
		{
			return csrfToken();
		}
	}