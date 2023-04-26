<?php
	
	namespace Marwa\Application\Notification\Mailer;
	
	use Marwa\Application\Notification\Mailer\Interfaces\MailBuilderInterface;
	
	abstract class MailBuilder implements MailBuilderInterface {
		
		/**
		 * @var MailMessage
		 */
		protected $message;
		
		/**
		 * @return mixed
		 */
		abstract public function build();
		
		/**
		 * @param $name
		 * @param $arguments
		 * @return $this
		 */
		public function __call( $name, $arguments )
		{
			if(!isset($this->message))
			{
				$this->message = MailMessage::getInstance();
			}
			return call_user_func_array([$this->message,$name],$arguments);
		}
	}
