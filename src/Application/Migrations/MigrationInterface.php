<?php
	
	namespace Marwa\Application\Migrations;
	
	interface MigrationInterface {
		
		/**
		 * @return void
		 */
		public function up() : void;
		
		/**
		 * @return void
		 */
		public function down() : void;
		
	}
