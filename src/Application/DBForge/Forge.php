<?php
	
	namespace Marwa\Application\DBForge;
	
	
	class Forge {
		
		
		/**
		 * @param $tableSql
		 * @return mixed
		 */
		public static function createTable( $tableSql )
		{
			return app('DB')->rawQuery($tableSql);
		}
		
		/**
		 * @param string $table
		 * @return int
		 */
		public static function dropIfExists( string $table )
		{
			if ( static::tableExists($table) )
			{
				return static::dropTable($table);
			}
			
			return 0;
			
		}
		
		/**
		 * @param string $table
		 * @return mixed
		 */
		public static function tableExists( string $table )
		{
			return app('DB')->rawQuery("SHOW TABLES LIKE '{$table}'");
		}
		
		
		/**
		 * @param string $table
		 * @return mixed
		 */
		public static function dropTable( string $table )
		{
			return app('DB')->rawQuery("DROP TABLE {$table}");
		}
		
		
		/**
		 * @param string $table
		 * @return mixed
		 */
		public static function optimize( string $table )
		{
			return app('DB')->rawQuery("OPTIMIZE TABLE `{$table}`");
		}
		
		
		/**
		 * @param string $table
		 * @return mixed
		 */
		public static function repair( string $table )
		{

			return app('DB')->rawQuery("REPAIR TABLE `{$table}`");
		}
		
		
		/**
		 * @param string $table
		 * @return mixed
		 */
		public static function empty( string $table )
		{
			
			return app('DB')->rawQuery("TRUNCATE `{$table}`");
		}
		
		
		/**
		 * @param string $table
		 * @return mixed
		 */
		public static function show( string $table )
		{
			return app('DB')->rawQuery("SHOW CREATE TABLE `{$table}`");
		}
		
	}
