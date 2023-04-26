<?php
	
	namespace Marwa\Application\Notification\Mailer;
	
	use Marwa\Application\Notification\Mailer\Interfaces\MessageInterface;
	use Swift_Attachment;
	use Swift_Image;
	use Swift_Message;
	
	class MailMessage implements MessageInterface {
		
		/**
		 * @var MailMessage
		 */
		protected static $instance;
		
		/**
		 * @var Swift_Message
		 */
		protected $message;
		/**
		 * @var string
		 */
		protected $body;
		
		/**
		 * MailMessage constructor.
		 * @param string $subject
		 */
		private function __construct( string $subject = "" )
		{
			$this->message = new Swift_Message($subject);
		}
		
		/**
		 * @param string $subject
		 * @return MailMessage
		 */
		public static function getInstance( string $subject = '' )
		{
			if ( !isset(static::$instance) )
			{
				static::$instance = new MailMessage($subject);
			}
			
			return static::$instance;
		}
		
		/**
		 * @return Swift_Message
		 */
		public function getMessage() : Swift_Message
		{
			return $this->message;
		}
		
		/**
		 * [from description] mail from
		 *
		 * @param array $from [description]
		 * @return self       [description]
		 */
		public function from( $from ) : MessageInterface
		
		{
			$this->message->setFrom($from);
			
			return $this;
		}
		
		/**
		 * [to description] mail to
		 *
		 * @param array $to [description]
		 * @return MessageInterface
		 *      [description]
		 */
		public function to( $to ) : MessageInterface
		
		{
			$this->message->setTo($to);
			
			return $this;
		}
		
		/**
		 * [cc description] mail cc
		 *
		 * @param array $cc [description]
		 * @return MessageInterface
		 *      [description]
		 */
		public function cc( $cc ) : MessageInterface
		
		{
			$this->message->setCc($cc);
			
			return $this;
		}
		
		/**
		 * [bcc description] mail bcc
		 *
		 * @param array $bcc [description]
		 * @return MessageInterface
		 *       [description]
		 */
		public function bcc( $bcc ) : MessageInterface
		
		{
			$this->message->setBcc($bcc);
			
			return $this;
		}
		
		/**
		 * [subject description] mail subject
		 *
		 * @param string $title [description]
		 * @return MessageInterface
		 *          [description]
		 */
		public function subject( $title ) : MessageInterface
		
		{
			$this->message->setSubject($title);
			
			return $this;
		}
		
		/**
		 * [sender description]
		 *
		 * @param string $sender [description]
		 * @return MessageInterface
		 *           [description]
		 */
		public function sender( $sender ) : MessageInterface
		
		{
			$this->message->setSender($sender);
			
			return $this;
		}
		
		/**
		 * [body description]
		 *
		 * @param string $body [description]
		 * @param string $mime [description]
		 * @return MessageInterface
		 *         [description]
		 */
		public function body( $body, $mime = 'text/html' ) : MessageInterface
		
		{
			$this->message->setBody($body, $mime);
			
			return $this;
		}
		
		/**
		 * [concat description]
		 *
		 * @param string $body [description]
		 * @param string $mime [description]
		 * @return MessageInterface
		 *         [description]
		 */
		public function concat( $body, $mime = 'text/html' ) : MessageInterface
		
		{
			$this->message->addPart($body, $mime);
			
			return $this;
		}
		
		/**
		 * [replyTo description]
		 *
		 * @param string $email [description]
		 * @return MessageInterface
		 *          [description]
		 */
		public function replyTo( $email ) : MessageInterface
		
		{
			$this->message->setReplyTo($email);
			
			return $this;
		}
		
		/**
		 * [read description]
		 *
		 * @param string $email [description]
		 * @return MessageInterface
		 *          [description]
		 */
		public function read( $email ) : MessageInterface
		
		{
			$this->message->setReadReceiptTo($email);
			
			return $this;
		}
		
		/**
		 * [charset description]
		 *
		 * @param string $char [description]
		 * @return MessageInterface
		 *         [description]
		 */
		public function charset( $char ) : MessageInterface
		
		{
			$this->message->setCharset($char);
			
			return $this;
		}
		
		/**
		 * @param int $priority
		 * @return $this
		 */
		public function priority( $priority ) : MessageInterface
		
		{
			$this->message->setPriority($priority);
			
			return $this;
		}
		
		/**
		 * @return \Swift_Mime_SimpleHeaderSet
		 */
		public function getHeader()
		{
			return $this->message->getHeaders();
		}
		
		/**
		 * @param string $email
		 * @return $this
		 */
		public function return( $email ) : MessageInterface
		
		{
			$this->message->setReturnPath($email);
			
			return $this;
		}
		
		/**
		 * @param string $file
		 * @return $this
		 */
		public function embed( $file ) : MessageInterface
		
		{
			$this->message->embed(Swift_Image::fromPath($file));
			
			return $this;
		}
		
		/**
		 * @param string $file
		 * @param $data
		 * @return mixed
		 */
		public function view( $file, $data = [] ) : MessageInterface
		{
			$this->body = app('view')->raw($file, $data);
			$this->message->setBody($this->body);
			
			return $this;
		}
		
		/**
		 * @param string $file
		 * @param array $data
		 * @return $this
		 */
		public function text( $file, $data = [] ) : MessageInterface
		{
			$this->body = app('view')->raw($file, $data);
			$this->message->setBody($this->body, 'text/plain');
			
			return $this;
		}
		
		/**
		 * [attach description] mail attachment
		 *
		 * @param string $file [description]
		 * @param array $params [description]
		 * @return MessageInterface
		 *           [description]
		 */
		public function attach( $file, array $params = [] ) : MessageInterface
		{
			$attachment = Swift_Attachment::fromPath($file);
			
			/**
			 * if attachment configuration provide
			 */
			if ( !empty($params) )
			{
				/**
				 *  if set attachment alias name then set alias
				 */
				if ( array_key_exists('as', $this->params) )
				{
					$attachment->setFileName($params['as']);
				}
				/**
				 * if mime set on the array then configure it
				 */
				if ( array_key_exists('mime', $params) )
				{
					$attachment->setContentType($params['mime']);
				}
				/**
				 *  if inline image attached key found in the config then attach it
				 */
				if ( array_key_exists('display', $params) )
				{
					$attachment->setDisposition('inline');
				}
			}
			$this->message->attach($attachment);
			
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function __toString()
		{
			return $this->body;
		}
		
		
	}
