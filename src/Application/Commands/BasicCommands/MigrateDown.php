<?php

namespace Marwa\Application\Commands\BasicCommands;

use Marwa\Application\Commands\AbstractCommand;
use Marwa\Application\Commands\MigrationCommandTrait;

class MigrateDown extends AbstractCommand
{

	use MigrationCommandTrait;

	/**
	 * @var string
	 */
	var $name = "migrate:down {--id}";

	/**
	 * @var string
	 */
	var $description = "This command will down migration";

	/**
	 * @var string
	 */
	var $help = "To Migrate down use this command i.e  migrate:down {--id}";

	/**
	 * [$argTitle description]
	 *
	 * @var array
	 */
	var $argTitle = ['id' => "Please provide migration id"];

	/**
	 *
	 */
	public function handle(): void
	{
		$this->warn('CAUTION: This operation will down tables. You may loose data');
		$answer = $this->ask("Are you sure to run down migration ? (y/N)");
		if (!$answer || strtolower($answer) === 'n') {
			$this->error("Migration down cancelled");
		}

		$this->info("Migration Down Task has Starting... ");

		$id = $this->option('id');

		if (!isset($id) or empty($id)) {
			$appliedFiles = $this->getMigrationPending(true);
			if (empty($appliedFiles)) {
				$this->info("Nothing to Migrate down");
				die();
			} else {
				$this->requiredOnce($appliedFiles);
			}
			$this->runDown($appliedFiles);

		} else {
			$file = $this->getMigrateFilesById($id, true);
			$this->requiredOnce($file);
			$this->runDownById($file[$id], $id);
		}

	}

	/**
	 * @param array $pendingFiles
	 */
	public function runDown(array $pendingFiles)
	{
		foreach ($pendingFiles as $id => $file) {
			$this->runDownById($file, $id);
		}
	}

	/**
	 * @param $file
	 * @param $id
	 */
	public function runDownById($file, $id)
	{
		[$id, $className] = explode('_', $file);

		try {
			$obj = new $className();
			$obj->down();
			$this->updateMigrationHistory($id, 'pending');
			$this->info("Successfully migration down for " . $file);
		} catch (Exception $e) {
			$this->info("Failed to execute migration down for " . $file);
			$this->error($e->getMessage());
		}
	}


}