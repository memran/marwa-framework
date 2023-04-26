<?php
	
	
	namespace Marwa\Application\Notification\Broadcasts;
	
	abstract class BroadcastBuilder {
		
		/**
		 * @var string
		 */
		protected $channel;
		/**
		 * @var string
		 */
		protected $event;
		
		/**
		 * @return string
		 * @throws \Exception
		 */
		public function getChannel() : string
		{
			if ( !is_null($this->channel) )
			{
				return $this->channel;
			}
			else
			{
				throw new \Exception('Channel not set');
			}
		}
		
		/**
		 * @param string $channel
		 * @return $this
		 */
		public function setChannel( string $channel )
		{
			$this->channel = $channel;
			
			return $this;
		}
		
		/**
		 * @return string
		 * @throws \Exception
		 */
		public function getEvent() : string
		{
			if ( !is_null($this->event) )
			{
				return $this->event;
			}
			else
			{
				throw new \Exception('Event not set');
			}
			
		}
		
		/**
		 * @param string $event
		 * @return $this
		 */
		public function setEvent( string $event ) : self
		{
			$this->event = $event;
			
			return $this;
		}
		
		/**
		 * @return mixed
		 */
		abstract public function push();
		
	}
