<?php
	
	namespace Marwa\Application\Commands\BasicCommands;
	
	use Marwa\Application\Commands\AbstractCommand;
	use Marwa\Application\Commands\ConsoleCommandTrait;
	use Marwa\Application\Commands\MigrationCommandTrait;
	
	
	class MakeModel extends AbstractCommand {
		
		use ConsoleCommandTrait;
		use MigrationCommandTrait;
		
		/**
		 * @var string
		 */
		var $name = "make:model {name} {--table}";
		/**
		 * @var string
		 */
		var $description = "It will generate model";
		/**
		 * @var string
		 */
		var $help = "Use this command to generate new model usage : make:model {name} {--table}";
		/**
		 * @var array
		 */
		var $argTitle = [
			'name' => "Please enter model name",
			'table' => "Please enter table name",
		];
		
		/**
		 *
		 */
		public function handle() : void
		{
			$modelName = $this->argument("name");
			if ( empty($modelName) )
			{
				$this->error("Model name not supplied");
			}
			
			$tableName = $this->option('table');
			if ( empty($tableName) )
			{
				$tableName = "CHANGE_ME";
			}
			
			//generate migration time
			$modelFile = ucfirst($modelName);
			//replace the string
			$data = [
				'CLASSNAME' => ucfirst($modelName),
				'TABLE' => $tableName
			];
			
			$this->setWriteDirPath($this->getModelPath());
			
			/**
			 * Generate migration file from template
			 */
			$result = $this->generateFileFromTemplate('NewModel', $modelFile, $data);
			/**
			 * If migration generation is success then store information to database
			 * otherwise throw error
			 */
			if ( $result )
			{
				$this->info("Successfully generated model in " . $this->getModelPath().$modelFile);
			}
			else
			{
				$this->error("Failed to generate model in " . $this->getModelPath().$modelFile);
			}
		}
		
		/**
		 * @return string
		 */
		public function getModelPath()
		{
			return WEBROOT . DIRECTORY_SEPARATOR . "App" . DIRECTORY_SEPARATOR . "Models" . DIRECTORY_SEPARATOR;
		}
		
	}
