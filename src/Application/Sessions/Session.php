<?php
	declare( strict_types = 1 );
	
	namespace Marwa\Application\Sessions;
	
	use Aura\Session\SessionFactory;
	
	class Session implements SessionServiceInterface {
		
		/**
		 * [protected description]
		 *
		 * @var array
		 */
		protected $config = [];
		
		/**
		 * [protected description]
		 *
		 * @var SessionFactory
		 */
		protected $instance;
		/**
		 * [protected description]
		 *
		 * @var \Aura\Session\Session
		 */
		protected $session;
		/**
		 * [protected description] segment name
		 *
		 * @var string
		 */
		protected $segment = 'Marwa\Application';
		/**
		 * [protected description] default session storage
		 *
		 * @var string
		 */
		protected $storage = 'default';
		
		/**
		 * Session constructor.
		 */
		public function __construct()
		{
			$this->config = app('config')->load('session.php');
			$this->createFactory();
		}
		
		/**
		 * @return Session|mixed
		 */
		public function createFactory()
		{
			$session_factory = new SessionFactory();
			$this->instance = $session_factory->newInstance($_COOKIE);
			$this->session = $this->getSection();
			$this->initConfig();
			
			return $this->session;
		}
		
		/**
		 * @return mixed
		 */
		public function getSection()
		{
			return $this->instance->getSegment($this->segment);
		}
		
		/**
		 *
		 */
		public function initConfig() : void
		{
			if ( array_key_exists('segment', $this->config) && !is_null($this->config['segment']) )
			{
				$this->setSection($this->config['segment']);
			}
			if ( array_key_exists('lifetime', $this->config) && !is_null($this->config['lifetime']) )
			{
				$this->expire((int) $this->config['lifetime']);
			}
			if ( array_key_exists('storage', $this->config) && !is_null($this->config['storage']) )
			{
				$this->storage($this->config['storage']);
			}
		}
		
		/**
		 * @param string $name
		 */
		public function setSection( string $name )
		{
			$this->segment = trim($name);
		}
		
		/**
		 * @param int $ttl
		 * @return $this
		 */
		public function expire( int $ttl ) : self
		{
			$this->instance->setCookieParams(['lifetime' => $ttl]);
			
			return $this;
		}
		
		/**
		 * @param string $name
		 * @return $this
		 */
		public function storage( string $name ) : self
		{
			$this->storage = trim($name);
			
			return $this;
		}
		
		/**
		 * @param string $key
		 * @return mixed
		 */
		public function isValid( string $key )
		{
			return $this->csrfToken()->isValid($key);
		}
		
		/**
		 * @return mixed
		 */
		public function csrfToken()
		{
			return $this->instance->getCsrfToken();
		}
		
		/**
		 * @return mixed
		 */
		public function csrfTokenValue()
		{
			return $this->instance->getCsrfToken()->getValue();
		}
		
		/**
		 *
		 */
		public function destroy() : void
		{
			$this->instance->destroy();
		}
		
		/**
		 *
		 */
		public function regenerate() : void
		{
			$this->instance->regenerateId();
		}
		
		/**
		 * @param string $method
		 * @param mixed $params
		 * @return mixed
		 */
		public function __call( string $method, $params )
		{
			return call_user_func_array([$this->session, $method], $params);
		}
	}
