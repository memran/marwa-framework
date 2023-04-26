<?php
	
	namespace Marwa\Application\Notification\Broadcasts;
	
	class Broadcast implements BroadcastClientInterface {
		
		
		/**
		 * @param BroadcastBuilder $v
		 * @return mixed
		 * @throws \Exception
		 */
		public function process( BroadcastBuilder $v )
		{
			if ( method_exists($v, 'push') )
			{
				return $v->push();
			}
			else
			{
				throw new \Exception('BroadcastBuilder must be implement \'push\' method');
			}
		}
		
		
	}
