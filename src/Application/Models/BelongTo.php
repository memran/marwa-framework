<?php
	
	
	namespace Marwa\Application\Models;
	
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	
	class BelongTo implements RelationInterface {
		
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
		protected $foreignId;
		
		public function __construct( Model $foreignModel, Model $localModel, $foreign_key, $local_key = null )
		{
			$this->foreignModel = $foreignModel;
			$this->localModel = $localModel;
			$this->foreignId = $this->localModel->$foreign_key;
			
			$this->createRelation();
		}
		
		/**
		 * @throws InvalidArgumentException
		 */
		public function createRelation()
		{
			$this->foreignModel->where($this->foreignModel->getPrimaryKey(), '=', $this->foreignId);
		}
		
		/**
		 * @return Model
		 */
		public function getRelation()
		{
			return $this->foreignModel->all();
		}
	}
	
	