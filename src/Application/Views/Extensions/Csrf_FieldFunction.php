<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class Csrf_FieldFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @return string
		 * @throws FileNotFoundException
		 */
		public function getCsrf_FieldFunction()
		{
			return '<input type="hidden" name="__csrf_value" id="__csrf_value" value="' . csrfToken() . '">';
		}
	}