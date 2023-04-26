<?php
	declare( strict_types = 1 );
	
	
	namespace Marwa\Application\Notification\SMS;
	
	
	abstract class SMSBuilder {
		
		/**
		 * [public description]
		 *
		 * @var string
		 */
		public $to;
		
		/**
		 * [public description]
		 *
		 * @var string
		 */
		public $from;
		
		/**
		 * [public description]
		 *
		 * @var string
		 */
		public $body;
		
		/**
		 * [getTo description]
		 *
		 * @return string [description]
		 */
		public function getTo() : string
		{
			return $this->to;
		}
		
		/**
		 * [setTo description]
		 *
		 * @param string $to [description]
		 * @return self       [description]
		 */
		public function setTo( string $to ) : self
		{
			$this->to = $to;
			
			return $this;
		}
		
		/**
		 * [getFrom description]
		 *
		 * @return string [description]
		 */
		public function getFrom() : string
		{
			return $this->from;
		}
		
		/**
		 * [setFrom description]
		 *
		 * @param string $from [description]
		 * @return self         [description]
		 */
		public function setFrom( string $from ) : self
		{
			$this->from = $from;
			
			return $this;
		}
		
		/**
		 * [getBody description]
		 *
		 * @return string [description]
		 */
		public function getBody() : string
		{
			return $this->body;
		}
		
		/**
		 * [setBody description]
		 *
		 * @param string $body [description]
		 * @return self         [description]
		 */
		public function setBody( string $body ) : self
		{
			$this->body = $body;
			
			return $this;
		}
		
		abstract public function send();
	}
