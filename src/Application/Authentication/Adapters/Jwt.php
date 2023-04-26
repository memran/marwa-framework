<?php
	/**
	 * @author    Mohammad Emran <memran.dhk@gmail.com>
	 * @copyright 2018
	 *
	 * @see https://www.github.com/memran
	 * @see http://www.memran.me
	 **/
	
	namespace Marwa\Application\Authentication\Adapters;
	
	class Jwt implements AuthenticationInterface {
		
		/**
		 * @var array options for password hash
		 */
		protected $_options = [
			'cost' => 10
		];
		
		/**
		 * [$token description]
		 *
		 * @var null
		 */
		protected $token = null;
		
		//token expiration
		protected $tokenExpire = 60;
		
		//jwt token decoded users
		protected $session_user;
		
		/**
		 * [$user description]
		 *
		 * @var null
		 */
		protected $user = null;
		
		/**
		 * [__construct description] construct
		 */
		public function __construct()
		{
			$options = app('appConfig')->file('auth.php')->getConfig();
			if ( !is_null($options) )
			{
				$this->tokenExpire = $options['token_expire'];
				$this->_options = $options['auth'];
			}
		}
		
		/**
		 * @param string $username
		 * @param string $password
		 * @return bool
		 */
		public function check( string $username, string $password ) : bool
		{
		
		}
		
		/**
		 * @return bool
		 */
		public function valid() : bool
		{
			
			return false;
		}
		
		
		/**
		 *  PASSWORD HASH verification
		 *
		 * @param string password
		 * @param string password_hash
		 * @return boolean
		 */
		public function verify( string $password, string $hash ) : bool
		{
			return password_verify($password, $hash);
			
		}
		
		public function scope( $scopes )
		{
		
		}
		
		public function getScope()
		{
		
		}
		
		
	}

?>
