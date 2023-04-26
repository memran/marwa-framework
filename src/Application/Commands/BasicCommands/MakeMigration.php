<?php
	
	namespace Marwa\Application\Commands\BasicCommands;
	
	use Marwa\Application\Commands\AbstractCommand;
	use Marwa\Application\Commands\ConsoleCommandTrait;
	use Marwa\Application\Commands\MigrationCommandTrait;
	
	
	class MakeMigration extends AbstractCommand {
		
		use ConsoleCommandTrait;
		use MigrationCommandTrait;
		
		/**
		 * @var string
		 */
		var $name = "make:migration {name} {--table} {--desc}";
		/**
		 * @var string
		 */
		var $description = "It will generate migration";
		/**
		 * @var string
		 */
		var $help = "Use this command to generate new migration usage : make:migration {name} {--table} {--desc}";
		/**
		 * @var string[]
		 */
		var $argTitle = [
			'name' => "Please enter migration name",
			'table' => "Please put table name",
			"desc" => "Comment for migration table"
		];
		
		/**
		 *
		 */
		public function handle() : void
		{
			$migrationName = $this->argument("name");
			if ( empty($migrationName) )
			{
				die("migration name not supplied");
			}
			
			$tableName = $this->option('table');
			if ( empty($tableName) )
			{
				$tableName = "CHANGE_ME";
			}
			$desc = $this->option('desc');
			if ( empty($desc) )
			{
				$this->error("Please set migration description with --desc option");
				die;
			}
			//generate migration time
			$id = time();
			$migrationFile = $id . '_' . $migrationName;
			//replace the string
			$data = [
				'MIGRATIONNAME' => $migrationName,
				'MIGRATIONTABLE' => $tableName
			];
			
			$this->setWriteDirPath($this->getMigrationPath());
			
			/**
			 * Generate migration file from template
			 */
			$result = $this->generateFileFromTemplate('NewMigration', $migrationFile, $data);
			/**
			 * If migration generation is success then store information to database
			 * otherwise throw error
			 */
			if ( $result )
			{
				$this->migrationHistory($id, $migrationFile, $desc);
				$this->info("Successfully generated migration file " . $migrationFile);
			}
			else
			{
				$this->error("Failed to generate migration file " . $migrationFile);
			}
		}
		
	}
