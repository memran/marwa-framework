<?php
	
	
	namespace Marwa\Application\Views\Extensions;
	
	use Marwa\Application\Views\Interfaces\TwigExtensionInterface;
	
	class NoticeFunction implements TwigExtensionInterface {
		
		public function __construct()
		{
		}
		
		/**
		 * @param string $key
		 * @param bool $dismissal
		 * @return string
		 */
		public function getNoticeFunction( string $key, bool $dismissal = false )
		{
			return notice($key, $dismissal);
		}
	}