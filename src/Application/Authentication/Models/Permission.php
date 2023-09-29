<?php
	namespace Marwa\Application\Authentication\Models;
	
	use Marwa\Application\Models\BelongsToMany;
	use Marwa\Application\Models\Model;
	
	class Permission extends Model {
		
		/**
		 * @var string
		 */
		protected $table = 'permission';
		
		/**
		 * @var string
		 */
		protected $fillable = ['name', 'guard_name'];
		/**
		 * @var bool
		 */
		protected $timestamps = false;
		/**
		 * @var array
		 */
		protected $hidden = [];
		
		/**
		 * @return BelongsToMany
		 */
		public function role()
		{
			return $this->belongsToMany('App\Models\Role', 'role_has_permission');
			
		}
		
	}
