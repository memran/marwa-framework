<?php
	
	namespace Marwa\Application\Authentication;
	
	use App\Models\Role;
	use App\Models\User;
	use Exception;
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Facades\Auth;
	use Marwa\Application\Utils\Hash;
	
	class AuthorizedUser {
		
		/**
		 * @var User
		 */
		protected $_user;
		/**
		 * @var Hash
		 */
		protected $_session_user;
		/**
		 * @var Role
		 */
		protected $_role;
		/**
		 * @var array
		 */
		protected $permissions = [];
		
		/**
		 * Profile constructor.
		 * @param array $user
		 * @throws InvalidArgumentException
		 */
		public function __construct( $user )
		{
			$this->_session_user = Hash::from((array) $user);
			$this->loadUserPermission();
		}
		
		/**
		 * @throws InvalidArgumentException
		 */
		protected function loadUserPermission()
		{
			/**
			 * Find user in the database
			 */
			$this->_user = new User();
			
			//Find User
			$this->_user->find($this->_session_user->id);
			
			if ( !$this->_user->exists() )
			{
				throw new Exception("User not found");
			}
			//Load Role details
			$this->_role = $this->_user->role;
			
			/**
			 * Load permissions and set on the container for access
			 */
			$this->permissions = $this->_user->role->permission->collect()->pluck('guard_name');
		}
		
		/**
		 * @return User
		 */
		public function getUser()
		{
			//return container()->get('auth_user');
			return $this->_user;
		}
		
		/**
		 * @return mixed
		 */
		public function getId()
		{
			return $this->_session_user->id;
		}
		
		/**
		 * @return mixed
		 */
		public function name()
		{
			return $this->_session_user->name;
		}
		
		/**
		 * @return array
		 */
		public function role()
		{
			return $this->_role->toArray();
		}
		
		/**
		 * @return bool
		 */
		public function isAdmin()
		{
			if ( ucfirst((string) $this->_role->name) === 'Admin' )
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * @return bool
		 */
		public function isUser()
		{
			if ( ucfirst((string) $this->_role->name) === 'User' )
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * @return bool
		 */
		public function isMember()
		{
			if ( ucfirst((string) $this->_role->name) === 'Member' )
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * @return array
		 */
		public function getPermissions()
		{
			return $this->permissions;
		}
		/**
		 * @param string $permission
		 * @return bool
		 */
		public function allow( string $permission )
		{
			if ( in_array($permission, $this->getPermissions()) )
			{
				return true;
			}
			
			return false;
		}
		
		
	}
