<?php
	
	namespace Marwa\Application\Notification;
	
	class Notify {
		
		/**
		 * @return array|int|object|string
		 */
		public function mail()
		{
			return app('mail');
		}
		
		/**
		 * @return array|int|object|string
		 */
		public function sms()
		{
			return app('sms');
		}
		
		/**
		 * @return array|int|object|string
		 */
		public function broadcast()
		{
			return app('broadcast');
		}
		
		/**
		 * @return array|int|object|string
		 */
		public function voice()
		{
			return app('voice');
		}
		
		
	}
