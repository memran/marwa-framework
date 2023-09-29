<?php
	namespace Marwa\Application\Authentication\Models;
	
	
	use Exception;
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Facades\DB;
	
	trait HasPermissions {
		
		/**
		 * @param $permission
		 * @return mixed
		 * @throws Exception
		 */
		public function givePermissionTo( $permission )
		{
			$perm = $this->getPermissionModel($permission);
			
			return DB::table('role_has_permission')->insert(['role_id' => $this->getId(), 'permission_id' => $perm->id]);
		}
		
		/**
		 * @param $permission
		 * @return Permission|array|mixed
		 * @throws InvalidArgumentException
		 */
		protected function getPermissionModel( $permission )
		{
			if ( is_string($permission) )
			{
				$perModel = ( new Permission() )->findBy('guard_name', $permission);
			}
			elseif ( is_int($permission) )
			{
				$perModel = ( new Permission() )->find($permission);
			}
			else
			{
				throw new Exception("Invalid Permission name");
			}
			
			if ( !$perModel->exists() )
			{
				throw new Exception("Permission not found");
			}
			
			return $perModel;
		}
		
		public function revokePermissionTo( $permission )
		{
		
		}
		
		public function hasPermissionTo( $permission )
		{
		
		}
		
		public function syncPermission( array $lists )
		{
			DB::beginTrans();
			try
			{
				DB::table('role_has_permission')->where('role_id', '=', $this->getId())->delete();
				$tempItems = [];
				foreach ( $lists as $key => $value )
				{
					$tempItem['role_id'] = $this->getId();
					$tempItem ['permission_id'] = $value;
					array_push($tempItems, $tempItem);
				}
		
				$result = DB::table('role_has_permission')->insert($tempItems);
				if ( !$result )
				{
					throw new Exception("Failed to assign permission");
				}
				DB::commit();
				
				return $result;
			} catch ( Exception $e )
			{
				DB::rollback();
				setMessage('error', 'errors', $e->getMessage());
			}
			return false;
			
		}
		
		public function hasAnyPermission()
		{
		
		}
		
		public function hasAllPermissions()
		{
		
		}
		
		public function getAllPermissions()
		{
		
		}
		
		
		public function hasAllDirectPermissions( $roles )
		{
		
		}
		
		public function hasAnyDirectPermission( $permission )
		{
		
		}
	}
