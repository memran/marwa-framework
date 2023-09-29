<?php
	namespace Marwa\Application\Authentication;
	use Marwa\Application\Utils\Hash;
	use Nette\Utils\Callback;
	use Marwa\Application\Facades\Auth;
	use Marwa\Application\Authentication\Exceptions\{InvalidArgumentException,InvalidAuthentication};
	use Marwa\Application\Authentication\Models\User;

	class Gates {
		/**
		 * @@var array description
		 * */
		protected $_gate_list=[];
		/**
		 * Current User
		 * */
		protected $_currentUser=null;

		/**
		 * @@var callable description
		 * */
		protected $_before=null;

		/**
		 * @@var callable description
		 * */
		protected $_after = null;

		/**
		 * Construction Gate Class
		 * */
		public function __construct() {

		}
		/**
		 * Return the list of gate define
		 * @@return array description
		 * **/
		public function list() : array
		{
			return $this->_gate_list;
		}
		/**
		 * function to define the Gate function
		 * @@param string $name description
		 * @@@param string $callable description
		 * @@return void description
		 * */
		public function define(string $ability, mixed $callback)
		{
			$this->_gate_list[$ability] = $callback;
		}

		/**
		 * Valided action for a specific user with policy class
		 * @@return bool description
		 * */
		public function authorize(string $ability,$params=null)
		{
			if(!$this->allows($ability,$params))
			{
				throw new InvalidAuthentication("You are not permitted");
			}
			return true;
		}
		/**
		 * execute gate defined callback string
		 * */
		protected function execute(string $ability,$params=null)
		{
			$res=null;
			if(array_key_exists($ability, $this->_gate_list))
			{
				$_callback=$this->_gate_list[$ability];
			}
			else {
				throw new InvalidArgumentException("Invalid ability to ask permission");
			}
			try
			{
				//check before callable function and execute
				if(is_callable($this->_before))
		 		{
		 			$res=call_user_func_array($this->_before,[$this->_currentUser,$ability]);
		 		}
		 		// check if function string are callable
				if(is_callable($_callback))
				{
					if(!Callback::check($_callback))
					{
			 			throw new InvalidArgumentException("Invalid Function format");
			 		}
			 		//store the result
					$res=call_user_func_array($_callback,[$this->_currentUser,$params]);
				}
				else if(is_array($_callback)) //if callback argument supplied array
				{
					$_policy_class = new $_callback[0](); //create the policy class
					$res=call_user_func_array([$_policy_class,$_callback[1]],[$this->_currentUser,$params]);
				}
				// if after variable is callable
				if(is_callable($this->_after))
		 		{
		 			$res=call_user_func_array($this->_before,[$this->_currentUser,$ability,$res,$params]);
		 		}
		 		//finally return the result
		 		if($res != null) return $res;

			} catch(Exceptions $e){
					throw new InvalidArgumentException("Invalid function Argument :".$e->getMessage());
				}


		}
		/**
		 * @param $guard_name
		 * @return mixed
		 */
		public function allows(string $ability,$params=null)
		{
			$this->_currentUser=Auth::user();
			if(is_string($params))
			{
				$params = new $params();
			}
			return $this->execute($ability,$params);
		}
		
		/**
		 * @param $guard_name
		 * @return bool
		 */
		public function denies(string $ability,$params=null)
		{
			return !Auth::allow($ability,$params);
		}
		/**
		 *	The user can update or delete the post
		 * */
		public function any(array $abilities,$params=null)
		{
			foreach($abilities as $k => $v)
			{
				if(!$this->allows($v,$params))
				{
					return false;
				}
			}
			return true;
		}
		/**
		 *  The user cannot update or delete the post
		 * */
		public function none(array $abilities,$params=null)
		{
			foreach($abilities as $k => $v)
				{
					if(!$this->allows($v,$params))
					{
						return true;
					}
				}
				return false;
		}
		/**
		 *  check if The user can create the post...
		 * */

		public function check(string $ability,array $params=[])
		{
				return $this->allows($ability,$params);
		}
		/**
		 *  if the currently authenticated user is authorized to perform a given action without writing a dedicated gate
		 * */
		public function allowIf(callable $_callback)
		{
			if(!Callback::check($_callback))
			{
	 			throw new InvalidArgumentException("Invalid Function format");
	 		}
			return call_user_func_array($_callback,Auth::user());
		}
		/**
		 *  if the currently authenticated user is note authorized to perform a given action without writing a dedicated gate
		 * */
		public function denyIf(callable $_callback)
		{
			if(!Callback::check($_callback))
			{
	 			throw new InvalidArgumentException("Invalid Function format");
	 		}
			return !call_user_func_array($_callback,Auth::user());
		}
		/**
		 * it is run before all other authorization checks
		 * */
		public function before(callable $_callback)
		{
			$this->_before=$_callback;
			return $this;
		}
		/**
		 * it is run after all other authorization checks
		 * */
		public function after(callable $_callback)
		{
			$this->_after=$_callback;
			return $this;
		}
		/**
		 * If you would like to determine if a user other than the currently authenticated user is authorized to perform an action, you may use the forUser method
		 * */
		public function forUser(User $user)
		{
			$this->_currentUser = $user;
			return $this;
		}

	}
