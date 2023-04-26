<?php
	
	namespace Marwa\Application\Commands\BasicCommands;
	
	use Exception;
	use Marwa\Application\Commands\AbstractCommand;
	use Marwa\Application\Commands\MigrationCommandTrait;
	
	class MigrateUp extends AbstractCommand {
		
		use MigrationCommandTrait;
		
		/**
		 * [$name description]
		 *
		 * @var string
		 */
		var $name = "migrate:up {--id}";
		
		/**
		 * [$description description]
		 *
		 * @var string
		 */
		var $description = "Migration Up Script";
		
		/**
		 * [$help description]
		 *
		 * @var string
		 */
		var $help = "migrate:up {--id}. This command will up all pending migration";
		/**
		 * [$argTitle description]
		 *
		 * @var array
		 */
		var $argTitle = ['id' => "Please provide migration id"];
		
		/**
		 * @throws Exception
		 */
		public function handle() : void
		{
			//check migration table exists
			if ( !$this->hasTable() )
			{
				$this->error("'migration' table does not exists on the database");
				die();
			}
			$this->info("Migration Up Processing..");
			$id = $this->option('id');
			
			if ( empty($id) )
			{
				$pendingFiles = $this->getMigrationPending();
				if ( !$pendingFiles || empty($pendingFiles) )
				{
					$this->info("Nothing to Migrate up");
					die();
				}
				else
				{
					$this->requiredOnce($pendingFiles);
				}
				$this->runUp($pendingFiles);
				
			}
			else
			{
				$file = $this->getMigrateFilesById($id);
				$this->requiredOnce($file);
				$this->runUpById($file[ $id ], $id);
			}
			
		}
		
		/**
		 * @param array $pendingFiles
		 * @throws Exception
		 */
		public function runUp( array $pendingFiles )
		{
			foreach ( $pendingFiles as $id => $file )
			{
				//$this->info($id);
				$this->runUpById($file, $id);
			}
		}
		
		/**
		 * @param $file
		 * @param $id
		 * @throws Exception
		 */
		public function runUpById( $file, $id )
		{
			try
			{
				[$file_id, $className] = explode('_', $file);
				
				$mig = new $className();
				//call_user_func_array([$mig,'up'],[]);
				$mig->up();
				$applied_at = now()->timestamp;
				$this->updateMigrationHistory($id, $applied_at);
				$this->info("Successfully migrated " . $file);
			} catch ( \Throwable $th )
			{
				throw new Exception($th);
			}
			
		}
		
		
	}
