<?php
	
	namespace Marwa\Application\Migrations;
	
	use Marwa\Application\Facades\DB;
	
	abstract class AbstractSeeder implements SeederInterface {
		
		/**
		 * @param string $sqlString
		 * @param array $params
		 * @return mixed
		 */
		public function execute( string $sqlString, array $params = [] )
		{
			return DB::rawQuery($sqlString, $params);
		}
		
		/**
		 *
		 */
		abstract public function run() : void;
	}
