<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Carbon\Carbon;
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class NowFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param array $opts
		 * @return Carbon
		 */
		public function getNowFunction( $opts = null )
		{
			return now($opts);
		}
	}