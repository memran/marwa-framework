<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class AssetFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param string $url
		 * @return string
		 * @throws InvalidArgumentException
		 */
		public function getAssetFunction( $url )
		{
			return asset($url);
		}
	}