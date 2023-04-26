<?php
	
	namespace Marwa\Application\Authentication\Adapters;
	
	use Exception;
	use Marwa\Application\Authentication\Exceptions\InvalidArgumentException;
	
	class Database implements AuthenticationInterface {
		
		/**
		 * @var array
		 */
		protected $_config = [];
		
		
		/**
		 * @var string[]
		 */
		protected $_scope = ['id', 'name', 'username', 'remember_token'];
		/**
		 * @var array
		 */
		protected $_user;
		
		/**
		 * Db constructor.
		 *
		 * @param array $config
		 */
		public function __construct( array $config )
		{
			$this->_config = $config;
		}
		
		/**
		 * @param array $scopes
		 */
		public function scope( array $scopes )
		{
			$this->_scope = $scopes;
		}
		
		/**
		 * @return array
		 * @throws Exception
		 */
		public function getScope()
		{
			$filtered = [];
			if ( empty($this->_user) )
			{
				throw new Exception("Session is not authorized");
			}
			
			foreach ( $this->_user as $k => $v )
			{
				if ( in_array($k, $this->_scope) )
				{
					$filtered[ $k ] = $v;
				}
			}
			
			return $filtered;
		}
		
		/**
		 * @param string $username
		 * @param string $password
		 * @return bool
		 * @throws InvalidArgumentException
		 */
		public function check( string $username, string $password ) : bool
		{
			if ( nullEmpty($username) || nullEmpty($password) )
			{
				return false;
			}
			/**
			 * Locate username in the database
			 * if not found then return false otherwise go for password verification
			 */
			$user = $this->findUserByUsername($username);
			
			if ( !$user )
			{
				return false;
			}
			
			/**
			 *  if password is verify  successfully then setUser as authenticate user otherwise
			 *  return false
			 */
			if ( $this->verify($password, $user->password) )
			{
				$this->setAuthenticateUser($user);
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * @param string $username
		 * @return bool
		 * @throws InvalidArgumentException
		 */
		protected function findUserByUsername( string $username )
		{
			
			/**
			 * Create the User model
			 * set where condition
			 * Return true if found user in the database
			 */
			$res = $this->getUserModel()->findBy('username', $username);
			if ( empty($res) )
			{
				return false;
			}
			/**
			 * if user is inactive then return false
			 */
			if ( $res->active == 0 )
			{
				return false;
			}
			
			/**
			 * return user without array index
			 */
			return $res;
		}
		
		/**
		 * @return mixed
		 * @throws InvalidArgumentException
		 */
		protected function getUserModel()
		{
			if ( !array_key_exists('model', $this->_config) )
			{
				throw new InvalidArgumentException("Model not found in configuration");
			}
			
			return new $this->_config['model'];
		}
		
		/**
		 * @param string $password
		 * @param string $hash
		 * @return bool
		 */
		public function verify( string $password, string $hash ) : bool
		{
			return password_verify($password, $hash);
		}
		
		/**
		 * @param $user
		 */
		private function setAuthenticateUser( $user )
		{
			/**
			 * Initialize the scope
			 */
			$scopeUser = [];
			/**
			 *  Loop through the scope and put that on array
			 */
			foreach ( $this->_scope as $key => $val )
			{
				$scopeUser[ $val ] = $user->$val;
			}
			$this->_user = $scopeUser;
		}
		
		/**
		 * @return mixed
		 */
		public function getAuthenticateUser() : array
		{
			return $this->_user;
		}
		
		/**
		 * @param string $token
		 * @return bool
		 * @throws InvalidArgumentException
		 */
		public function checkRemember( string $token ) : bool
		{
			$user = $this->findUserByRemember($token);
			
			if ( empty($user) || !$user )
			{
				return false;
			}
			else
			{
				$this->setAuthenticateUser($user);
				
				return true;
			}
		}
		
		/**
		 * @param string $token
		 * @return bool
		 * @throws InvalidArgumentException
		 */
		protected function findUserByRemember( string $token )
		{
			/**
			 * Create the User model
			 * set where condition
			 * Return true if found user in the database
			 */
			$res = $this->getUserModel()->findBy('remember_token', $token);
			
			if ( !$res )
			{
				return false;
			}
			/**
			 * if user is inactive then return false
			 */
			if ( $res->active == 0 )
			{
				return false;
			}
			
			/**
			 * return user without array index
			 */
			return reset($res);
		}
		
		
	}
