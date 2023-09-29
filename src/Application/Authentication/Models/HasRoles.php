<?php
	namespace Marwa\Application\Authentication\Models;

	use Exception;
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Facades\DB;
	
	trait HasRoles {
		
		/**
		 * @return mixed
		 * @throws Exception
		 */
		public function getRoleName()
		{
			if ( $this->exists() )
			{
				return $this->role->name;
			}
			else
			{
				throw new Exception("User not selected");
			}
		}
		
		/**
		 * @return mixed
		 * @throws Exception
		 */
		public function getRoleId()
		{
			if ( $this->exists() )
			{
				return $this->role->id;
			}
			else
			{
				throw new Exception("User not selected");
			}
		}
		
		/**
		 * @param $role
		 * @return mixed
		 * @throws InvalidArgumentException
		 */
		public function changeRole( $role )
		{
			$roleModel = $this->getRole($role);
			
			return DB::table('user_has_role')->where('user_id', '=', $this->getId())->update(['role_id' => $roleModel->id]);
		}
		
		/**
		 * @param $role
		 * @return Role|array|mixed
		 * @throws InvalidArgumentException
		 */
		protected function getRole( $role )
		{
			if ( is_string($role) )
			{
				$roleModel = ( new Role() )->findBy('name', $role);
			}
			elseif ( is_int($role) )
			{
				$roleModel = ( new Role() )->find($role);
			}
			else
			{
				throw new Exception("Invalid Role Type");
			}
			
			if ( !$roleModel->exists() )
			{
				throw new Exception("Role not found");
			}
			
			return $roleModel;
		}
		
		/**
		 * @param $role
		 * @return mixed
		 * @throws Exception
		 */
		public function removeRole( $role )
		{
			$roleModel = $this->getRole($role);
			
			return DB::table('user_has_role')->where('role_id', '=', $roleModel->id)->delete();
			
		}
		
		/**
		 * @param $role
		 * @return mixed
		 * @throws Exception
		 */
		public function assignRole( $role )
		{
			$roleModel = $this->getRole($role);
			
			return DB::table('user_has_role')->insert(['user_id' => $this->getId(), 'role_id' => $roleModel->id]);
		}
		
		public function syncRoles( $role )
		{
		
		}
		
		public function hasRole( $role )
		{
		
		}
		
		public function hasAnyRole( $roles )
		{
		
		}
		
	}
