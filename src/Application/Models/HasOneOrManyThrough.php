<?php
	
	
	namespace Marwa\Application\Models;
	
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	
	class HasOneOrManyThrough implements RelationInterface {
		
		/**
		 * @var Model
		 */
		protected $farModel;
		/**
		 * @var Model
		 */
		protected $interModel;
		/**
		 * @var Model
		 */
		protected $parentModel;
		/**
		 * @var string
		 */
		protected $foreignKey;
		/**
		 * @var string
		 */
		protected $firstLocalKey;
		/**
		 * @var string
		 */
		protected $secondLocalKey;
		/**
		 * @var string
		 */
		protected $interForeignKey;
		
		/**
		 * HasOneOrManyThrough constructor.
		 * @param Model $farModel
		 * @param Model $interModel
		 * @param Model $parent
		 * @param string $foreignKey
		 * @param string $interForeignKey
		 * @param string $firstLocalKey
		 * @param string $secondLocalKey
		 * @throws InvalidArgumentException
		 * @throws InvalidPrimaryKey
		 */
		public function __construct( Model $farModel, Model $interModel, Model $parent, $foreignKey, $interForeignKey, $firstLocalKey , $secondLocalKey )
		{
			
			$this->farModel = $farModel;
			$this->interModel = $interModel;
			$this->parentModel = $parent;
			$this->foreignKey = $foreignKey;
			$this->interForeignKey = $interForeignKey;
			$this->firstLocalKey = $firstLocalKey;
			$this->secondLocalKey = $secondLocalKey;
			$this->createRelation();
		}
		
		/**
		 * @return Model|mixed
		 */
		public function getRelation()
		{
			return $this->farModel->all();
		}
		
		/**
		 * @throws InvalidPrimaryKey
		 * @throws InvalidArgumentException
		 */
		protected function createRelation()
		{
			
			$this->farModel->join($this->interModel->getTable(),
			                $this->farModel->getTable() . '.' . $this->interForeignKey, '=',
			                $this->interModel->getTable() . '.' . $this->secondLocalKey
			);
			$this->farModel->join($this->parentModel->getTable(),
			                $this->parentModel->getTable() . '.' . $this->firstLocalKey, '=',
			                $this->interModel->getTable() . '.' . $this->foreignKey
			);
			$this->farModel->where($this->interModel->getTable() . '.' . $this->foreignKey, '=', $this->parentModel->getId());
		}
	}