<?php
	
	namespace Marwa\Application\Notification\Mailer;
	
	use Exception;
	use Marwa\Application\Notification\Mailer\Interfaces\MailBuilderInterface;
	
	
	class Mail {
		
		/**
		 * @var array
		 */
		protected $config;
		
		/**
		 * @var string
		 */
		protected $mailer = 'smtp';
		
		/**
		 * @var string
		 */
		
		protected $default;
		/**
		 * @var array
		 */
		
		protected $from = [];
		/**
		 * @var array
		 */
		protected $queue = [];
		
		/**
		 * Mail constructor.
		 * @param array $config
		 */
		public function __construct( array $config )
		{
			$this->setConfigs($config);
		}
		
		/**
		 * @param array $config
		 */
		protected function setConfigs( array $config )
		{
			$this->config = $config;
			$this->setDefaultMailer($config['default']);
			$this->from($config['from']);
		}
		
		/**
		 * @param $mailer
		 * @return $this
		 */
		public function setDefaultMailer( $mailer )
		{
			if ( !empty($mailer) )
			{
				$this->default = $mailer;
				
			}
			
			return $this;
		}
		
		/**
		 * @param $from
		 * @return $this
		 */
		public function from( $from )
		{
			if ( !empty($from) )
			{
				$this->from = $from;
			}
			
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getDefaultMailer()
		{
			return $this->mailer;
		}
		
		/**
		 * @param string $name
		 * @return $this
		 */
		public function mailer( string $name )
		{
			if ( !empty($name) )
			{
				$this->mailer = $name;
			}
			
			return $this;
		}
		
		/**
		 * @param MailBuilder $mail
		 * @throws Exception
		 */
		public function send( MailBuilderInterface $mail )
		{
			if ( $mail instanceof MailBuilderInterface )
			{
				$message = $mail->build();
				$message->from($this->getFrom());
				
				return MailerFactory::create($this->getMailer(),
				                             $this->getConfig()
				)->send(
					$message->getMessage()
				);
			}
			else
			{
				throw new Exception($mail . ' Class did not implement MailBuilderInterface');
			}
		}
		
		/**
		 * @return bool|mixed
		 */
		public function getFrom()
		{
			return $this->from;
		}
		
		/**
		 * @return string
		 */
		protected function getMailer() : string
		{
			return $this->mailer;
		}
		
		/**
		 * @param string $key
		 * @return mixed
		 */
		protected function getConfig( string $key = null )
		{
			if ( !is_null($key) )
			{
				return $this->config[ $this->getMailer() ][ $key ];
			}
			
			return $this->config[ $this->getMailer() ];
		}
		
		/**
		 * @param $name
		 * @param $arguments
		 * @return $this
		 */
		public function __call( $name, $arguments )
		{
			$mailer = MailMessage::getInstance();
			call_user_func_array([$mailer, $name], $arguments);
			
			return $this;
		}
		
		public function queue( MailBuilderInterface $mail )
		{
			array_push($this->queue, $mail);
			
			return $this;
		}
		
		public function later( $sendingTime, MailBuilderInterface $mail )
		{
		
		}
		
		public function delay( MailBuilderInterface $mail, int $delay = 5 )
		{
		
		}
		
	}
	
