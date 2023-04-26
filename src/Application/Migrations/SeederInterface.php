<?php
	
	namespace Marwa\Application\Migrations;
	
	interface SeederInterface {
		
		/**
		 * @return void
		 */
		public function run() : void;
		
	}
