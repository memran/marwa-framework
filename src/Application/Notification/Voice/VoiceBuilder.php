<?php
	declare( strict_types = 1 );
	
	namespace Marwa\Application\Notification\Voice;
	
	abstract class VoiceBuilder {
		
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
		
		abstract public function call();
	}
