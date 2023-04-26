<?php
	
	namespace Marwa\Application\Notification\Voice;
	
	interface VoiceClientInterface {
		
		public function process( VoiceBuilder $v );
		
	}
