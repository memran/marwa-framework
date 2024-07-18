<?php

namespace Marwa\Application\Models;

use Exception;
use Marwa\Application\Exceptions\InvalidArgumentException;

trait Relation
{

	/**
	 * @param $related
	 * @param null $foreign_key
	 * @param null $local_key
	 * @return HasOne
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 */
	public function hasOne($related, $foreign_key = null, $local_key = null)
	{
		if (is_null($foreign_key)) {
			$foreign_key = $this->getForeignKey();
		}

		/**
		 * Create relational Class
		 */
		$instance = new $related;

		return new HasOne($instance, $this, $foreign_key, $local_key);
	}

	/**
	 * @param $related
	 * @param null $foreign_key
	 * @param null $local_key
	 * @return HasMany
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 */
	public function hasMany($related, $foreign_key = null, $local_key = null)
	{
		if (is_null($foreign_key)) {
			$foreign_key = $this->getForeignKey();
		}

		/**
		 * Create relational Class
		 */
		$instance = new $related;

		return new HasMany($instance, $this, $foreign_key, $local_key);
	}
	/**
	 * @return string
	 */
	public function getForeignKey()
	{
		return $this->getTable() . '_' . $this->getPrimaryKey();
	}

	/**
	 * @param $belongClass
	 * @param null $foreign_key
	 * @param null $local_key
	 * @return BelongTo
	 */
	public function belongsTo($belongClass, $foreign_key = null, $local_key = null)
	{
		/**
		 * Create relational Class
		 */
		$instance = new $belongClass;
		/**
		 * if foreign key not supplied then detect auto
		 */
		if (is_null($foreign_key)) {
			$foreign_key = $instance->getForeignKey();
		}

		return new BelongTo($instance, $this, $foreign_key, $local_key);
	}

	/**
	 * @param $belongClass
	 * @param null $joinClass
	 * @param null $foreignKey_relative
	 * @param null $foreignKey_local
	 * @return BelongsToMany
	 */
	public function belongsToMany($belongClass, $joinClass = null, $foreignKey_relative = null, $foreignKey_local = null)
	{
		/**
		 * Create relational Class
		 */
		$relative = new $belongClass;
		if (is_null($joinClass)) {
			$joinClass = $relative->getTable() . '_' . $this->getTable();
		}
		/**
		 * If relative foreign ket is null then fetch foreign key from relative class
		 * and store it
		 */
		if (is_null($foreignKey_relative)) {
			$foreignKey_relative = $relative->getForeignKey();
		}
		/**
		 * If foreign key of local is null then fetch it from class
		 */
		if (is_null($foreignKey_local)) {
			$foreignKey_local = $this->getForeignKey();
		}

		return new BelongsToMany($relative, $this, $joinClass, $foreignKey_relative, $foreignKey_local);
	}

	/**
	 * @param $remoteModel
	 * @param $relative
	 * @param null $foreignKey
	 * @param null $remoteForeignKey
	 * @param null $firstLocalKey
	 * @param null $secondLocalKey
	 * @return HasOneOrManyThrough
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 */
	public function hasManyThrough($remoteModel, $relative, $foreignKey = null, $remoteForeignKey = null, $firstLocalKey = null, $secondLocalKey = null)
	{
		return $this->hasOneThrough($remoteModel, $relative, $foreignKey = null, $remoteForeignKey = null, $firstLocalKey = null, $secondLocalKey = null);
	}

	/**
	 * @param $remoteModel
	 * @param $relative
	 * @param null $foreignKey
	 * @param null $remoteForeignKey
	 * @param null $firstLocalKey
	 * @param null $secondLocalKey
	 * @return HasOneOrManyThrough
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 */
	public function hasOneThrough($remoteModel, $relative, $foreignKey = null, $remoteForeignKey = null, $firstLocalKey = null, $secondLocalKey = null)
	{
		/**
		 * Create the Remote Model
		 */
		$farModel = new $remoteModel();
		/**
		 * Create Intermediate Model
		 */
		$interModel = new $relative();

		/**
		 * If foreign key is not set then determine automatically from the local class
		 */
		if (is_null($foreignKey)) {
			$foreignKey = $this->getForeignKey();
		}
		/**
		 * Remote Foreign Key is Intermediate foreign key.If It is null then determine the foreign key from Intermediate Model
		 */
		if (is_null($remoteForeignKey)) {
			$remoteForeignKey = $interModel->getForeignKey();
		}
		/**
		 * First Local key is local class primary key. Determine automatically if key is not provided
		 */
		if (is_null($firstLocalKey)) {
			$firstLocalKey = $this->getPrimaryKey();
		}
		/**
		 * Second Local key is Intermediate primary key.If it is not provided then determine form intermediate model
		 */
		if (is_null($secondLocalKey)) {
			$secondLocalKey = $interModel->getPrimaryKey();
		}

		/**
		 *  Create the HasOneOrManyThrough class and return the Far/Remote Model
		 */
		return new HasOneOrManyThrough($farModel, $interModel, $this, $foreignKey = null, $remoteForeignKey = null, $firstLocalKey = null, $secondLocalKey = null);

	}

	/**
	 * @param $relative
	 * @param null $callable
	 * @return $this
	 * @throws InvalidArgumentException
	 */
	public function with($relative, $callable = null)
	{
		if (is_string($relative)) {
			$with = new WithRelation($relative, $this);
			if (!is_null($callable)) {
				$callable($with->getBuilder());
			}
			$with->buildRelation();
			return $this;
		} else if (is_array($relative)) {
			foreach ($relative as $k => $item) {
				$with = new WithRelation($item, $this);
				$with->buildRelation();
			}
			return $this;
		} else {
			throw new InvalidArgumentException("Invalid relational table name");
		}
	}


}
