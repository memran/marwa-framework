<?php
	
	
	namespace Marwa\Application\Models;
	
	use Exception;
	use MarwaDB\DB;
	
	class QueryBuilder {
		
		/**
		 * @var \MarwaDB\QueryBuilder
		 */
		protected $builder;
		/**
		 * @var DB
		 */
		protected $db;
		
		/**
		 * QueryBuilder constructor.
		 * @param string $table
		 * @param string $connection
		 * @throws Exception
		 */
		public function __construct( string $table, string $connection = null )
		{
			$this->db = app('DB');
			if ( isset($connection) )
			{
				$this->db->connection($connection);
			}
			if ( empty($table))
			{
				throw new Exception("Table name is empty!");
			}
			$this->builder = $this->db->table($table);
			
		}
		
		/**
		 * @return mixed
		 */
		public function getBuilder()
		{
			return $this->builder;
		}
		
		/**
		 * @return mixed
		 */
		public function getDb()
		{
			return $this->db;
		}
	}
