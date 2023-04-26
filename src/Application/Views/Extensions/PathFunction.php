<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class PathFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param string $url
		 * @param mixed $param
		 * @return string
		 * @throws InvalidArgumentException
		 */
		public function getPathFunction( string $url, $param = null )
		{
			$newUrl = $url;
			if ( !is_null($param) )
			{
				$newUrl = $newUrl . '/' . $param;
			}
			
			return base_url() .$newUrl;
		}
	}