<?php
	namespace Marwa\Application\Authentication\Models;
	
	use Marwa\Application\Models\Model;
	
	class Role extends Model {
		
		use HasPermissions;
		
		/**
		 * @var string
		 */
		protected $table = 'role';
		
		/**
		 * @var string
		 */
		protected $fillable = ['name', 'description'];
		/**
		 * @var array
		 */
		protected $hidden = [];
		
		
		public function user()
		{
			return $this->belongsToMany('App\Models\User', 'user_has_role');
		}
		
		public function permission()
		{
			return $this->belongsToMany('App\Models\Permission', 'role_has_permission');
		}
		
	}
