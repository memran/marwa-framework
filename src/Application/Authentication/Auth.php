<?php
	
	namespace Marwa\Application\Authentication;
	
	use App\Models\User;
	use Marwa\Application\Authentication\Adapters\AuthenticationInterface;
	use Marwa\Application\Authentication\Exceptions\InvalidArgumentException;
	use Marwa\Application\Authentication\Exceptions\InvalidAuthenticationDriver;
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Input;
	use Psr\Http\Message\ResponseInterface;
	
	class Auth {
		
		/**
		 * @var array
		 */
		protected $_config = [];
		
		/**
		 * @var string
		 */
		protected $_defaultAuthProvider = 'default';
		/**
		 * @var string
		 */
		protected $defaultAdapter = 'database';
		/**
		 * @var AuthorizedUser
		 */
		protected $authuser;
		/**
		 * @var array
		 */
		private $_user;
		
		/**
		 * Auth constructor.
		 * @param array $config
		 * @throws InvalidArgumentException
		 */
		public function __construct( array $config )
		{
			$this->setConfig($config);
		}
		
		/**
		 * @param array $config
		 * @throws InvalidArgumentException
		 */
		protected function setConfig( array $config ) : void
		{
			if ( empty($config) )
			{
				throw new InvalidArgumentException("Auth Configuration not found ");
			}
			/**
			 * Lookup if configuration exists provider name 'default' otherwise throw error
			 */
			if ( !isset($config[ $this->getDefaultAuthProvider() ]) )
			{
				throw new InvalidArgumentException("Default Provider not found ");
			}
			$this->_config = $config;
		}
		
		/**
		 * @return string
		 */
		public function getDefaultAuthProvider() : string
		{
			return $this->_defaultAuthProvider;
		}
		
		/**
		 * @param string $name
		 * @return $this
		 */
		public function provider( string $name )
		{
			$this->_defaultAuthProvider = $name;
			
			return $this;
		}
		
		/**
		 * @return ResponseInterface
		 * @throws FileNotFoundException
		 * @throws InvalidAuthenticationDriver
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function check()
		{
			$_auth = $this->createAuthenticationProvider();
			
			if ( !$_auth instanceof AuthenticationInterface )
			{
				throw new InvalidAuthenticationDriver("Invalid Authentication Adapter");
			}
			
			if ( !$_auth->check($this->getUsername(), $this->getPassword()) )
			{
				setMessage('error', 'errors', 'Access Denied');
				
				return redirect($this->getLoginUrl());
			}
			else
			{
				setMessage('success', 'authSuccess', 'Successfully Login');
				$this->postAuth($_auth->getAuthenticateUser());
				
				return redirect($this->getSuccessUrl());
			}
		}
		
		/**
		 * @throws Exceptions\InvalidAuthenticationDriver
		 */
		protected function createAuthenticationProvider()
		{
			return AuthFactory::getInstance($this->getDriver(), $this->getDriverConfig());
		}
		
		/**
		 * @return string
		 */
		public function getDriver() : string
		{
			if ( isset($this->_config[ $this->getDefaultAuthProvider() ]['adapter']) )
			{
				return ucfirst($this->_config[ $this->getDefaultAuthProvider() ]['adapter']);
			}
			
			return $this->defaultAdapter;
		}
		
		/**
		 * @return array
		 */
		protected function getDriverConfig() : array
		{
			return $this->_config[ $this->getDefaultAuthProvider() ];
		}
		
		/**
		 * @return bool|string|null
		 * @throws FileNotFoundException
		 */
		protected function getUsername()
		{
			return Input::post('username');
		}
		
		/**
		 * @return bool|string|null
		 * @throws FileNotFoundException
		 */
		protected function getPassword()
		{
			return Input::post('password');
		}
		
		/**
		 * @return mixed|string
		 */
		protected function getLoginUrl()
		{
			if ( isset($this->_config[ $this->getDefaultAuthProvider() ]['loginUrl']) )
			{
				return $this->_config[ $this->getDefaultAuthProvider() ]['loginUrl'];
			}
			
			return '/login';
		}
		
		/**
		 * @param $user
		 * @throws FileNotFoundException
		 */
		protected function postAuth( $user )
		{
			/**
			 * Save session authenticate user
			 */
			session("logged_in", true);
			
			/**
			 * Remember is checked then get rememberExpire time
			 * set remember token as session key 'token'
			 */
			if ( !is_null($this->getRemember()) )
			{
				session("token", $user['remember_token']);
			}
			unset($user['remember_token']);
			session("user", $user);
		}
		
		/**
		 * @return bool|string|null
		 * @throws FileNotFoundException
		 */
		protected function getRemember()
		{
			return Input::post('remember');
		}
		
		/**
		 * @return mixed
		 */
		protected function getSuccessUrl()
		{
			if ( isset($this->_config[ $this->getDefaultAuthProvider() ]['successUrl']) )
			{
				return $this->_config[ $this->getDefaultAuthProvider() ]['successUrl'];
			}
			
			return '/dashboard';
		}
		
		/**
		 * @return bool
		 * @throws FileNotFoundException
		 * @throws InvalidAuthenticationDriver
		 */
		public function valid() : bool
		{
			if ( !is_null(session('token')) )
			{
				return $this->checkRememberAuthentication(session('token'));
			}
			
			if ( session('logged_in') )
			{
				try
				{
					$this->authuser = new AuthorizedUser(session('user'));
					
					return true;
				} catch ( \Exception $e )
				{
					return false;
				}
			}
			
			return false;
		}
		
		/**
		 * @param string $token
		 * @return bool
		 * @throws InvalidAuthenticationDriver
		 */
		private function checkRememberAuthentication( string $token )
		{
			//$_auth = $this->createAuthenticationProvider();
			return $this->createAuthenticationProvider()->checkRemember($token);
		}
		
		/**
		 * @throws FileNotFoundException
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function logout()
		{
			app('session')->destroy();
			
			return redirect($this->getLoginUrl());
		}
		
		/**
		 * @return bool
		 * @throws FileNotFoundException
		 */
		public function isLoggedIn()
		{
			if ( session('logged_in') )
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * @return mixed|null
		 */
		public function id()
		{
			if ( isset($this->authuser) )
			{
				return $this->user()->id;
			}
			
			return false;
		}
		
		/**
		 * @return User
		 */
		public function user()
		{
			if ( isset($this->authuser) )
			{
				return $this->authuser->getUser();
			}
			
		}
		
		/**
		 * @return mixed|null
		 */
		public function role()
		{
			return $this->user()->role();
		}
		
		/**
		 * @param $name
		 * @param $arguments
		 * @return mixed
		 */
		public function __call( $name, $arguments )
		{
			if ( method_exists($this->authuser, $name) )
			{
				return call_user_func_array([$this->authuser, $name], $arguments);
			}
		}
		
		/**
		 * @return int|mixed
		 */
		protected function getExpireTime()
		{
			if ( isset($this->_config[ $this->getDefaultAuthProvider() ]['expire']) )
			{
				return $this->_config[ $this->getDefaultAuthProvider() ]['expire'];
			}
			
			return 3600;
		}
	}
