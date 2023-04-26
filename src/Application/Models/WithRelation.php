<?php
	
	
	namespace Marwa\Application\Models;
	
	use Exception;
	use Marwa\Application\Exceptions\InvalidArgumentException;
	
	
	class WithRelation {
		
		/**
		 * @var string
		 */
		protected $table;
		/**
		 * @var Model
		 */
		protected $parent;
		/**
		 * @var \MarwaDB\QueryBuilder
		 */
		protected $builder = null;
		/**
		 * @var array
		 */
		protected $collection;
		
		/**
		 * WithRelation constructor.
		 * @param string $table
		 * @param Model $parent
		 * @throws Exception
		 */
		public function __construct( string $table, Model $parent )
		{
			$this->table = $table;
			$this->parent = $parent;
	
			$this->createRelation();
		}
		
		/**
		 * @throws Exception
		 */
		protected function createRelation()
		{
			/**
			 * Set the wherein condition of child class
			 */
			$this->getBuilder()->whereIn($this->parent->getForeignKey(), $this->getCollectionIdFromParent());
		}
		
		/**
		 * @return mixed
		 * @throws Exception
		 */
		public function getBuilder()
		{
			if ( !isset($this->builder) )
			{
				$this->createChildModel();
			}
			
			/**
			 * Return MarwaDB/QueryBuilder
			 */
			return $this->builder->getBuilder();
		}
		
		/**
		 * @throws Exception
		 */
		protected function createChildModel()
		{
			/**
			 *  Read Child Class Table name and set to Query Builder
			 *  Get the \MarwaDB\QueryBuilder as return
			 */
			$this->builder = new QueryBuilder($this->getTable());
		}
		
		/**
		 * @return string
		 */
		protected function getTable()
		{
			return strtolower($this->table);
		}
		
		/**
		 * @return array
		 * @throws InvalidArgumentException
		 */
		protected function getCollectionIdFromParent()
		{
			/**
			 * Return the ID's array of parent model
			 */
			$this->collection = $this->parent->toCollect()->pluck($this->parent->getPrimaryKey());
		
			return $this->collection;
		}
		
		/**
		 * @return mixed
		 * @throws Exception
		 */
		public function buildRelation()
		{
			/**
			 *  Set child result to the parent model
			 */
			$this->setChildResultToParentModel($this->getBuilder()->get());
			return $this->getBuilder();
		}
		
		/**
		 * @param $result
		 * @throws Exception
		 */
		protected function setChildResultToParentModel($result)
		{
			/**
			 *  Convert child result to collection class
			 */
			$childCollection = collect($result);
			
			foreach ( $this->collection as $k => $v )
			{
				/**
				 *  Filter the collection with expression where foreign key is equal to parent model collection value
				 */
				$temp = $childCollection->where($this->parent->getForeignKey(),$v)->fetch();
				/**
				 * Finally append it to the model
				 */
				$this->parent->appendResult($k,$this->getTable(),$temp->toArray());
			}
		}
	}