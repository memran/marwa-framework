<?php
	
	namespace Marwa\Application\Models;
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	
	class HasMany implements RelationInterface {
		
		/**
		 * @var Model
		 */
		protected $localModel;
		/**
		 * @var Model
		 */
		protected $_parent;
		/**
		 * @var string
		 */
		protected $_foreign_key;
		/**
		 * @var string
		 */
		protected $_local_key;
		
		/**
		 * HasOne constructor.
		 * @param Model $instance
		 * @param Model $parent
		 * @param null $foreign_key
		 * @param null $local_key
		 * @throws InvalidArgumentException
		 * @throws InvalidPrimaryKey
		 */
		public function __construct( Model $instance, Model $parent, $foreign_key=null, $local_key=null)
		{
			/**
			 * store relative instance
			 */
			$this->localModel = $instance;
			/**
			 * Store caller instance
			 */
			$this->_parent = $parent;
			
			/**
			 * Foreign key
			 */
			$this->_foreign_key = $foreign_key;
			
			if(is_null($local_key))
			{
				$this->_local_key = $this->localModel->getPrimaryKey();
				
			}
			$this->createCondition();
		}
		
		/**
		 * @throws InvalidArgumentException
		 * @throws InvalidPrimaryKey
		 */
		protected function createCondition()
		{
			$this->localModel->where($this->_foreign_key,'=', $this->_parent->getId());
		}
		
		/**
		 * @return mixed
		 */
		public function getRelation()
		{
			return $this->localModel->all();
		}
	
	}
