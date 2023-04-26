<?php
	
	namespace Marwa\Application\Commands\BasicCommands;
	
	use Marwa\Application\Commands\AbstractCommand;
	use Marwa\Application\Commands\ConsoleCommandTrait;
	use Marwa\Application\Commands\MigrationCommandTrait;
	
	
	class MakeController extends AbstractCommand {
		
		use ConsoleCommandTrait;
		use MigrationCommandTrait;
		
		/**
		 * @var string
		 */
		var $name = "make:controller {name} {--base}";
		/**
		 * @var string
		 */
		var $description = "It will generate controller";
		/**
		 * @var string
		 */
		var $help = "Use this command to generate new controller usage : make:controller {name} {base}";
		/**
		 * @var array
		 */
		var $argTitle = [
			'name' => "Please enter controller name",
			'base' => "Please enter controller base i.e backend or frontend",
		];
		
		/**
		 *
		 */
		public function handle() : void
		{
			$controllerName = $this->argument("name");
			if ( empty($controllerName) )
			{
				$this->error("Controller name not supplied");
			}
			
			$controllerBase = $this->option("base");
			
			//generate migration time
			$controllerFile = $controllerName;
			//replace the string
			$data = [
				'CLASSNAME' => $controllerName
			];
			
			$this->setWriteDirPath($this->getControllerPath());
			
			if ( strtolower($controllerBase) == "backend" )
			{
				$tplName = 'NewBackendController';
			}
			else if ( strtolower($controllerBase) == "frontend" )
			{
				$tplName = 'NewFrontendController';
			}
			else
			{
				$tplName = 'NewController';
			}
			
			/**
			 * Generate migration file from template
			 */
			$result = $this->generateFileFromTemplate($tplName, $controllerFile, $data);
			/**
			 * If controller generation is success then print success message to console
			 * otherwise throw error
			 */
			if ( $result )
			{
				$this->info("Successfully generated controller in " . $this->getControllerPath() . $controllerFile);
			}
			else
			{
				$this->error("Failed to generate controller in " . $this->getControllerPath() . $controllerFile);
			}
		}
		
		/**
		 * @return string
		 */
		public function getControllerPath()
		{
			return WEBROOT . DIRECTORY_SEPARATOR . "App" . DIRECTORY_SEPARATOR . "Controllers" . DIRECTORY_SEPARATOR;
		}
		
	}
