<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class AppFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param null $instance
		 * @return array|int|object|string
		 */
		public function getAppFunction( $instance = null )
		{
			return app($instance);
		}
	}