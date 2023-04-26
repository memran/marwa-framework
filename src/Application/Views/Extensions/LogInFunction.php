<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Facades\Auth;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class LogInFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @return mixed
		 */
		public function getLogInFunction( )
		{
			return Auth::isLoggedIn();
		}
	}