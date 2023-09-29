<?php declare(strict_types=1);
	namespace Marwa\Application\Authentication;
	use Marwa\Application\Authentication\Models\User;
	use Marwa\Application\Authentication\Adapters\AuthenticationInterface;
	use Marwa\Application\Authentication\Exceptions\InvalidArgumentException;
	use Marwa\Application\Authentication\Exceptions\InvalidAuthentication;
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Input;
	use Psr\Http\Message\ResponseInterface;
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	use DateTimeImmutable;
	use Marwa\Application\Facades\Event;

	class Auth {
		
		/**
		 * @var array
		 */
		protected $_config = [];
		/**
		 * @var AuthorizedUser
		 */
		protected $_authenticated_user;
		/**
		 * @var object
		 */
		private $_user=null;

		/**
		 * @@var array description
		 * */
		private $_errors=[];

		/**
		 * User Session Remember
		 * */
		private $_remember=false;

		/**
		 * @@var boolean description
		 * */
		private $_ignore_session_set=false;

		/**
		 * @@var array description
		 *
		 * */
		private $_ignore =['password','remember_token','created_at','updated_at'];

		/**
		 * Auth constructor.
		 * @param array $config
		 * @throws InvalidArgumentException
		 */
		public function __construct( array $config )
		{
			$this->loadConfig($config);
		}
		
		/**
		 * @param array $config
		 * @throws InvalidArgumentException
		 */
		protected function loadConfig( array $config ) : void
		{
			if (count($config)==0)
			{
				throw new InvalidArgumentException("Auth Configuration not found: auth.php ");
			}
			$this->_config = $config;
		}
		/**
		 * @return mixed
		 * @throws InvalidArgumentException
		 */
		protected function createUserModel() : void
		{
			// if (!array_key_exists('model', $this->_config)) {
			// 	throw new InvalidArgumentException("Authentication Model is not found in configuration");
			// }
			if($this->_user == null )
			{
				//$this->_user = new $this->_config['model'];
				$this->_user = new User();
			}
		}
		/**
		 * @@param array $crediantial set the condition
		 * @@param boolean $remember generate rememberToken
		 * @@return boolean description
		 * */
		public function attempt(array $crediantial,bool $remember=false): bool
		{
			if(count($crediantial)==0)
			{
				throw new InvalidArgumentException("Crediantial array is empty or null.");
			}
			Event::fire('Attempting', $crediantial );
			//Set remember
			$this->_remember = $remember;
			// Create User Model Object
			$this->createUserModel();
			//create condition
			foreach($crediantial as $k=>$v){
				if(is_callable($v))
				{
					call_user_func_array($v,$this->_user->getQueryBuilder());
				}
				else if($k != 'password')
				{
					$this->_user->findBy($k,$v);
				}
			}
			$result =$this->_user->getResult();
			return $this->checkUserAuthentication($result,true,$crediantial);
		}
		/**
		 * check database result
		 * */
		private function checkUserAuthentication($result,$verify=false,$crediantial=[]): bool
		{
			if(!$result)
			{
				Event::fire('Failed',$this->_user);
				$this->setError('User Not Found',404);
				return false;
			}
			//Now verify password
			if($verify)
			{
				if(!$this->verify($crediantial['password'],$result['password']))
				{
					Event::fire('Failed',$this->_user);
					$this->setError('User/Password is Invalid',401);
					return false;
				}
			}
			Event::fire('Verified', $crediantial );
			if($result['active']==0)
			{
				Event::fire('Failed',$this->_user);
				$this->setError('User is Inactive',402);
				return false;
			}
			$this->postAuth($result);
			Event::fire('Login',$this->_authenticated_user);
			return true;
		}
		/**
		 * Specifying Additional Conditions
		 * */
		public function attemptWhen(array $crediantial, callable $func_name)
		{
			if(!is_callable($func_name))
			{
				throw new InvalidArgumentException("Invalid function format supplied.");
			}
			// attempt validate user
			if($this->attempt($crediantial))
			{
				//further logic is generated within function
				return call_user_func_array($func_name,$this->_user);
			}

		}

		/**
		 * setError Function to setup error message and code
		 * @@param string $errormsg description
		 * @@param int  $error_code description
		 * */
		private function setError(string $errormsg,int $error_code){
			$error = ['msg'=> $errormsg, 'error_code'=>$error_code];
			array_push($this->_errors,$error);
		}
		/**
		 * Retrieve Error Message Array
		 * @@return array description
		 * */
		public function getError():array
		{
			return $this->_errors;
		}
		/**
		 * Password Verification with has password
		 * @param string $password
		 * @param string $hash
		 * @return bool
		 */
		protected function verify(string $password, string $hash): bool
		{
			return password_verify($password, $hash);
		}

		/**
		 * Check User is logged in or not
		 * @return bool
		 * @throws InvalidArgumentException
		 */
		public function check(): bool
		{
			Event::fire('Validated', $crediantial );
			return $this->isLoggedIn();
		}

		/**
		 * @param $user
		 * @throws FileNotFoundException
		 */
		protected function postAuth( $user )
		{
			$this->_authenticated_user = $user;
			$this->filterColumn();
			//event fired
			Event::fire('Validated',$this->_authenticated_user);
			if($this->_ignore_session_set)
				return true;

			/**
			 * Save session authenticate user
			 */
			session("logged_in", true);
			
			/**
			 * Remember is checked then get rememberExpire time
			 * set remember token as session key 'token'
			 */
			if ( $this->getRemember())
			{
				session("rememberToken", $this->_authenticated_user['remember_token']);
			}

			session("user", $this->_authenticated_user);

		}
		/**
		 * filtering data
		 * */
		protected function filterColumn()
		{
			foreach ($this->_ignore as $k => $v)
			{
				unset($this->_authenticated_user[$v]);
			}
		}
		/**
		 * @@return boolean description
		 * */
		protected function getRemember(): bool
		{
			return $this->_remember;
		}
		/**
		 *  Get the currently authenticated user
		 * */
		public function user() : mixed
		{
			return $this->_user;
		}
		/**
		 * Get the currently authenticated user's ID...
		 * */
		public function id() : int
		{
			if(is_null($this->_authenticated_user))
			{
				throw new InvalidAuthentication("Invalid Auth ID");
				return 0;
			}
			//dd($this->_authenticated_user);
			return (int)$this->_authenticated_user['id'];
		}
		/**
		 * the user was authenticated using the "remember me" cookie
		 * */
		public function viaRemember()
		{
			if ( !is_null(session('rememberToken')) )
			{
				$result = $this->getUserModel()->where('remember_token','==',session('rememberToken'))->get();
				return $this->checkUserAuthentication($result,false);
			}
			return false;
		}
		/**
		 * Login and "remember" the given user...
		 * */
		public function login(User $user, bool $remember=false)
		{
			$crediantial=['username'=> $user->username,'password'=>$user->password];
			return $this->attempt($crediantial,$remember);
		}
		/**
		 * Login and "remember" the given user...
		 * */
		public function loginUsingId(int $id, bool $remember=false){
			$crediantial=['id' => $id];
			return $this->attempt($crediantial,$remember);
		}
		/**
		 * Authenticate A User Once
		 * */
		public function once(array $crediantial, bool $remember=false){
			$this->_ignore_session_set=true;
			return $this->attempt($crediantial, $remember);
		}

		/**
		 * Accessing Specific Guard Instances
		 * @@param string  $name description
		 * @@return Auth description
		 * */
		public function guard($name){
			if(Gate::allows($name)){
				return $this;
			}else {
					throw new InvalidAuthentication("Invalid Authentication");
			}
		}
		
		/**
		 * @throws FileNotFoundException
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function logout() : void
		{
			Event::fire('Logout',$this->_authenticated_user);
			app('session')->destroy();
		}
		
		/**
		 * @return bool
		 * @throws FileNotFoundException
		 */
		public function isLoggedIn() : bool
		{
			return session('logged_in');
		}
		/**
		 * Generate JWT Token
		 * */
		public function token(array $data)
		{
			$date   = new DateTimeImmutable();
			$expire_at     = $date->modify('+'.$this->_config['expire'].' seconds')->getTimestamp();      // Add 3660 seconds
			$request_data = [
			    'iat'  => $date->getTimestamp(),         // Issued at: time when the token was generated
			    'iss'  => $this->_config['iss'],                       // Issuer
			    'nbf'  => $date->getTimestamp(),         // Not before
			    'exp'  => $expire_at,                           // Expire
			    'data' => $data
			];
			 // Encode the array to a JWT string.
		    return  JWT::encode(
		        $request_data,
		        $this->_config['key'],
		        'HS512'
		    );
		}
		/**
		 * Check if JWT Token is Valid or not
		 * */
		public function isValid(string $token)
		{
			$decoded_token = JWT::decode($token, new Key($this->_config['key'], 'HS512'));
			$now = new DateTimeImmutable();
			if ($decoded_token->iss !== $this->_config['iss'] ||
			    $decoded_token->nbf > $now->getTimestamp() ||
			    $decoded_token->exp < $now->getTimestamp())
			{
			   return false;
			}
			Event::fire('Validated',$this->_authenticated_user);
			return true;
		}

	}
