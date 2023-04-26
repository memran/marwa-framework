<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Facades\Auth;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class AuthidFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @return mixed
		 */
		public function getAuthidFunction( )
		{
			return Auth::id();
		}
	}