<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Facades\Auth;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class IsAdminFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @return mixed
		 */
		public function getIsAdminFunction( )
		{
			return Auth::isAdmin();
		}
	}