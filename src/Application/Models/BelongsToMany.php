<?php
	
	
	namespace Marwa\Application\Models;
	
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	
	class BelongsToMany implements RelationInterface {
		
		/**
		 * @var Model
		 */
		protected $foreignModel;
		/**
		 * @var Model
		 */
		protected $localModel;
		/**
		 * @var mixed
		 */
		protected $foreignKey_relative;
		/**
		 * @var string
		 */
		protected $foreignKey_local;
		
		/**
		 * @var string
		 */
		protected $joinClass;
		
		protected $localId ;
		
		public function __construct( Model $belongClass,Model $localModel,$joinClass, $foreignKey_relative, $foreignKey_local)
		{
			$this->foreignModel = $belongClass;
			$this->localModel = $localModel;
			$this->joinClass = $joinClass;
			$this->foreignKey_relative = $foreignKey_relative;
			$this->foreignKey_local = $foreignKey_local;
			$this->localId = $this->localModel->getId();
			//create the relation
			$this->createRelation();
		
		}
		
		/**
		 * @return mixed
		 */
		protected function getForeignKeyRelative()
		{
			return $this->foreignKey_relative;
		}
		/**
		 * @throws InvalidArgumentException
		 */
		public function createRelation()
		{
			// select * from user LEFT JOIN user_role WHERE user.id = user_role.id AND user_role.role_id = 1
			$this->foreignModel->join($this->joinClass, $this->getForeignTableWithKey(), '=', $this->joinClass.'.'.$this->foreignKey_relative);
			$this->foreignModel->where($this->joinClass.'.'.$this->foreignKey_local, '=', $this->localId);
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		protected function getForeignTableWithKey()
		{
			return $this->foreignModel->getTable().'.'.$this->foreignModel->getPrimaryKey();
		}
		/**
		 * @return Model
		 */
		public function getRelation()
		{
			return $this->foreignModel->all();
		}
	}
	
	