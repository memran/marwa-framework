<?php
	declare( strict_types = 1 );
	/**
	 * @author    Mohammad Emran <memran.dhk@gmail.com>
	 * @copyright 2018
	 *
	 * @see https://www.github.com/memran
	 * @see http://www.memran.me
	 */
	
	namespace Marwa\Application\Events;
	
	use League\Event\ListenerInterface;
	
	abstract class AbstractListener implements ListenerInterface {
		
		/**
		 * @inheritdoc
		 */
		public function isListener( $listener )
		{
			return $this === $listener;
		}
	}
