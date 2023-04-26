<?php
	
	namespace Marwa\Application\Commands\BasicCommands;
	
	use Exception;
	use Marwa\Application\Commands\AbstractCommand;
	use Marwa\Application\Commands\MigrationCommandTrait;
	use Marwa\Application\Facades\File;
	
	class MigrateDrop extends AbstractCommand {
		
		use MigrationCommandTrait;
		
		/**
		 * @var string
		 */
		var $name = "migrate:drop {--id}";
		/**
		 * @var string
		 */
		var $description = "This command will drop migration files";
		
		/**
		 * @var string
		 */
		
		var $help = "To drop a migration file use this file. i.e. migrate:drop {--id}";
		/**
		 *
		 * @var array
		 */
		var $argTitle = ['id' => "Please provide migration id"];
		
		/**
		 *
		 */
		public function handle() : void
		{
			/**
			 * Ask for confirmation of migration drop
			 * If yes then proceed for next stage otherwise drop it
			 */
			$this->warn('CAUTION: This operation will drop tables and data');
			$answer = $this->ask("Are you sure to drop run migration ? (y/N)");
			if ( !$answer || strtolower($answer) === 'n' )
			{
				$this->error("Migration drop cancelled");
			}
			/**
			 *  If user entered other keyword than y/Y
			 */
			if ( strtolower($answer) !== 'y' )
			{
				$this->error("You need to press 'y' for successful operation");
			}
			/**
			 * Check migration table exists in the database
			 * if yes then proceed next step otherwise drop
			 */
			if ( !$this->hasTable() )
			{
				$this->error("'migration' table does not exists on the database");
			}
			
			$this->info("Migration Drop Starting... ");
			
			$id = $this->option('id');
			/**
			 * If migration id not provided then go for down all migration
			 *  Will reconfirm again for this operation
			 */
			if ( empty($id) )
			{
				
				$this->warn('CAUTION: This operation will drop all tables and You will loose all data');
				$answer = $this->ask("Are you sure to drop All migration ? (y/N)");
				
				if ( !$answer || strtolower($answer) === 'n' )
				{
					$this->error("Migration drop cancelled");
				}
				/**
				 *  If user entered other keyword than y/Y
				 */
				if ( strtolower($answer) !== 'y' )
				{
					$this->error("You need to press 'y' for successful operation");
				}
				
				$allFiles = toArray($this->getAllMigration());
				
				if ( empty($allFiles) )
				{
					$this->info("Nothing to drop");
					die();
				}
				$this->runDrop($allFiles);
				
			}
			else
			{
				$file = $this->getAllMigration($id);
				
				if ( empty($file) )
				{
					$this->info("Migration id not found in the database");
					die;
				}
				
				if ( $file[0]['applied_at'] != 'pending' )
				{
					$this->runDropById($file[0]['version']);
				}
				else
				{
					$this->info("Migration id is not available to drop");
					die;
				}
			}
		}
		
		/**
		 * @param $files
		 */
		public function runDrop( $files )
		{
			if ( !is_array($files) )
			{
				$this->error("Invalid Arguments.It is not array");
			}
			foreach ( $files as $key => $value )
			{
				//drop migration file
				$this->runDropById($value['version']);
			}
		}
		
		/**
		 * @param $file
		 * @param bool $down
		 */
		public function runDropById( $file, $down = false )
		{
			[$id, $className] = explode('_', $file);
			$filepath = $this->getMigrationPath() . $file . '.php';
			if ( file_exists($filepath) )
			{
				include_once( $filepath );
			}
			else
			{
				$this->error("File not found on the directory " . $filepath);
			}
			try
			{
				app('DB')->beginTrans();
				$mig = new $className();
				$mig->down();
				/**
				 * Delete migration id from database
				 */
				app('DB')->rawQuery("DELETE FROM migration WHERE id='{$id}'");
				/**
				 * Delete migration file. If delete is not successfully then throw error
				 */
				if ( !File::delete($filepath) )
				{
					throw new Exception("Migration file can not delete " . $filepath);
				}
				app('DB')->commit();
				$this->info("Successfully migration drop for " . $file);
			} catch ( \Throwable $th )
			{
				app('DB')->rollback();
			}
			
			
		}
		
	}
