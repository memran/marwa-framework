<?php
namespace Marwa\Application\Authentication\Models;
use Marwa\Application\Models\Model;
use Marwa\Application\Exceptions\InvalidArgumentException;
use Marwa\Application\Models\BelongsToMany;
use Marwa\Application\Models\HasOne;
use Marwa\Application\Models\InvalidPrimaryKey;
use Marwa\Application\Facades\Gate;

class User extends Model
{
	use HasRoles;
		/**
		 * @var string
		 */
		protected $table = 'user';

		/**
		 * @var string
		 */
		protected $fillable = ['name', 'username', 'email', 'active', 'password', 'remember_token'];
		/**
		 * @var bool
		 */
		protected $timestamps = true;
		/**
		 * @var string
		 */
		//protected $hidden = ['password', 'remember_token'];

		/**
		 * @return BelongsToMany
		 */
		public function role()
		{
			return $this->belongsToMany('Marwa\Application\Authentication\Models\Role', 'user_has_role');

		}

		/**
		 * User can do some specific action
		 * @@param string $ability description
		 * @@param array $params description
		 * @@return boolean description
		 * */
		public function can(string $ability,$params) : bool
		{
			try {
				return Gate::forUser($this)->allows($ability,$params);
			} catch(Exceptions $e)
			{
				throw new Exception($e->getMessage());
			}

		}
		/**
		 * User cant do some action
		 * @@return boolean description
		 * */
		public function cannot(string $ability,$params) : bool
		{
			try {
				return Gate::forUser($this)->denies($ability,$params);
			} catch(Exceptions $e)
			{
				throw new Exception($e->getMessage());
			}
		}


}
