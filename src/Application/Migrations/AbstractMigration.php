<?php
	
	namespace Marwa\Application\Migrations;
	
	abstract class AbstractMigration implements MigrationInterface {
		
		/**
		 * @param string $table
		 * @return TableSchema
		 */
		public function table( string $table )
		{
			return  new TableSchema($table);
			
		}
		
		/**
		 * @param $sqlString
		 * @param array $params
		 * @return mixed
		 */
		public function execute( $sqlString, $params = [] )
		{
			return app('DB')->rawQuery($sqlString, $params);
		}
		
		/**
		 * @return void
		 */
		abstract public function up() : void;
		
		/**
		 * @return void
		 */
		abstract public function down() : void;
		
		
	}
