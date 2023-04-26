<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class HiddenFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param string $id
		 * @param string $value
		 * @return string
		 */
		public function getHiddenFunction(string $id,string $value)
		{
			return '<input type="hidden" id="'.$id.'" name="'.$id.'" value="' . $value . '">';
		}
	}