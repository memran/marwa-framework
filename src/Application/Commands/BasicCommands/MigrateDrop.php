<?php

namespace Marwa\Application\Commands\BasicCommands;

use Exception;
use Marwa\Application\Commands\AbstractCommand;
use Marwa\Application\Commands\MigrationCommandTrait;
use Marwa\Application\Utils\{File, Arr};


class MigrateDrop extends AbstractCommand
{

	use MigrationCommandTrait;

	/**
	 * @var string
	 */
	var $name = "migrate:drop {--id}";
	/**
	 * @var string
	 */
	var $description = "This command will drop migration tables and files.";

	/**
	 * @var string
	 */

	var $help = "To drop a migration file  and table use this file. i.e. migrate:drop {--id}";
	/**
	 *
	 * @var array
	 */
	var $argTitle = ['id' => "Please provide migration id"];

	/**
	 *
	 */
	public function handle(): void
	{
		/**
		 * Ask for confirmation of migration drop
		 * If yes then proceed for next stage otherwise drop it
		 */
		$this->warn('CAUTION: This operation will drop tables and data');

		$answer = $this->ask("Are you sure to drop run migration ? (y/N)");
		if (!$answer || strtolower($answer) === 'n') {
			$this->error("Migration drop cancelled");
		}
		/**
		 *  If user entered other keyword than y/Y
		 */
		if (strtolower($answer) !== 'y') {
			$this->error("You need to press 'y' for successful operation");
		}
		/**
		 * Check migration table exists in the database
		 * if yes then proceed next step otherwise drop
		 */
		if (!$this->hasTable()) {
			$this->error("'migration' table does not exists on the database");
		}

		$this->info("Migration Drop Task has Started... ");

		$id = $this->option('id');
		/**
		 * If migration id not provided then go for down all migration
		 *  Will reconfirm again for this operation
		 */
		if (empty($id)) {
			//$this->info("Migration ID is not provided.We are droping all the tables.");
			$this->warn('CAUTION: This operation will drop all migration tables and You will loose all data');
			$answer = $this->ask("Are you sure to drop All migration tables and files? (y/N)");

			if (!$answer || strtolower($answer) == 'n') {
				$this->error("Migration drop cancelled");
			}
			/**
			 *  If user entered other keyword than y/Y
			 */
			if (strtolower($answer) != 'y') {
				$this->error("You need to press 'y' for successful operation");
			}

			$allFiles = toArray($this->getAllMigration());

			if (Arr::empty($allFiles)) {
				$this->error("Nothing to drop");
			}
			$this->runDrop($allFiles);

		} else {
			$file = $this->getAllMigration($id);

			if (empty($file)) {
				$this->error("Migration id not found in the database :" . $id);
			}

			if ($file[0]['applied_at'] == 'pending') {
				$this->runDropById($file[0]['version'], true);

			} else {
				$this->runDropById($file[0]['version'], false);

			}
		}
	}

	/**
	 * @param $files
	 */
	public function runDrop($files)
	{
		if (!is_array($files)) {
			$this->error("Invalid Arguments.It is not array");
		}
		foreach ($files as $key => $value) {
			//drop migration file
			$this->info("Droping Migration ID :" . $value['id']);
			if ($value['applied_at'] != "pending") {
				$this->runDropById($value['version'], true);
			} else {
				this->runDropById($value['version']);
			}

		}
	}

	/**
	 * @param $file
	 * @param bool $down
	 */
	public function runDropById($fileVersion, $down = false)
	{
		[$id, $className] = explode('_', $fileVersion);
		$filepath = $this->getMigrationPath() . $fileVersion . '.php';

		if (File::has($filepath)) {
			include_once($filepath);

			try {

				if ($down) {
					$mig = new $className();
					$mig->down();
					$this->info("Migration table down executed for ID:" . $id);
				}
				/**
				 * delete migration table row
				 */
				$this->deleteMigrationRowById($id);
				/**
				 * Delete migration file. If delete is not successfully then throw error
				 */
				File::delete($filepath);

				$this->info("Successfully migration drop for " . $fileVersion);
			} catch (\Throwable $th) {

				$this->info("Failed to execute  migration drop for " . $filepath);
				$this->error($th->getMessage());
			}
		} else {

			$this->info("Skipping for not existance of file :" . $filepath);
		}

	}



}