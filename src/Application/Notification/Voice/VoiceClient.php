<?php
	
	namespace Marwa\Application\Notification\Voice;
	
	class VoiceClient implements VoiceClientInterface {
		
		/**
		 * @param VoiceBuilder
		 * @return mixed
		 * @throws \Exception
		 */
		public function process( VoiceBuilder $v )
		{
			if ( method_exists($v, 'send') )
			{
				return $v->call();
			}
			else
			{
				throw new \Exception('VoiceBuilder must be implement \'call\' method');
			}
		}
		
		
	}
