<?php
	
	namespace Marwa\Application\Notification\SMS;
	
	class SMSClient implements SMSClientInterface {
		
		
		public function process( SMSBuilder $sms )
		{
			if ( method_exists($sms, 'send') )
			{
				return $sms->send();
			}
			else
			{
				throw new \Exception('SMSBuilder must be implement \'send\' method');
			}
		}
		
	}
