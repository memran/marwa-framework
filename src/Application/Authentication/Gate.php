<?php
	
	
	namespace Marwa\Application\Authentication;
	
	
	class Gate {
		
		public function __construct() {
		
		}
		
		/**
		 * @param $guard_name
		 * @return mixed
		 */
		public function allows($guard_name)
		{
				return Auth::allow($guard_name);
		}
		
		/**
		 * @param $guard_name
		 * @return bool
		 */
		public function deny($guard_name)
		{
			return !Auth::allow($guard_name);
		}
		
	}