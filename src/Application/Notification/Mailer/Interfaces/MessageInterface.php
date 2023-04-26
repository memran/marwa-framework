<?php
	
	namespace Marwa\Application\Notification\Mailer\Interfaces;
	
	use Swift_Message;
	
	interface MessageInterface {
		
		/**
		 * @return Swift_Message
		 */
		public function getMessage() : Swift_Message;
		
		/**
		 * [from description] mail from
		 *
		 * @param array $from [description]
		 * @return MessageInterface
		 *
		 */
		public function from( $from ) : MessageInterface;
		
		/**
		 * [to description] mail to
		 *
		 * @param array $to [description]
		 * @return MessageInterface
		 *
		 */
		public function to( $to ) : MessageInterface;
		
		/**
		 * [cc description] mail cc
		 *
		 * @param array $cc [description]
		 * @return MessageInterface
		 *
		 */
		public function cc( $cc ) : MessageInterface;
		
		/**
		 * [bcc description] mail bcc
		 *
		 * @param array $bcc [description]
		 * @return MessageInterface
		 *
		 */
		public function bcc( $bcc ) : MessageInterface;
		
		/**
		 * [subject description] mail subject
		 *
		 * @param string $title [description]
		 * @return MessageInterface
		 *
		 */
		public function subject( $title ) : MessageInterface;
		
		/**
		 * [sender description]
		 *
		 * @param string $sender [description]
		 * @return MessageInterface
		 *
		 */
		public function sender( $sender ) : MessageInterface;
		
		/**
		 * [body description]
		 *
		 * @param string $body [description]
		 * @param string $mime [description]
		 * @return MessageInterface
		 *
		 */
		public function body( $body, $mime = 'text/html' ) : MessageInterface;
		
		/**
		 * [concat description]
		 *
		 * @param string $body [description]
		 * @param string $mime [description]
		 * @return MessageInterface
		 *
		 */
		public function concat( $body, $mime = 'text/html' ) : MessageInterface;
		
		/**
		 * [replyTo description]
		 *
		 * @param string $email [description]
		 * @return MessageInterface
		 *
		 */
		public function replyTo( $email ) : MessageInterface;
		
		/**
		 * [read description]
		 *
		 * @param string $email [description]
		 * @return MessageInterface
		 *
		 */
		public function read( $email ) : MessageInterface;
		
		/**
		 * [charset description]
		 *
		 * @param string $char [description]
		 * @return MessageInterface
		 *
		 */
		public function charset( $char ) : MessageInterface;
		
		/**
		 * @param int $priority
		 * @return $this
		 */
		public function priority( $priority ) : MessageInterface;
		
		/**
		 * @return \Swift_Mime_SimpleHeaderSet
		 */
		public function getHeader();
		
		/**
		 * @param string $email
		 * @return $this
		 */
		public function return( $email ) : MessageInterface;
		
		/**
		 * @param string $file
		 * @return $this
		 */
		public function embed( $file ) : MessageInterface;
		
		/**
		 * @param string $file
		 * @param $data
		 * @return MessageInterface
		 */
		public function view( $file, $data = [] ) : MessageInterface;
		
		/**
		 * [attach description] mail attachment
		 *
		 * @param string $file [description]
		 * @param array $params [description]
		 * @return MessageInterface
		 *
		 */
		public function attach( $file, array $params = [] ) : MessageInterface;
	}