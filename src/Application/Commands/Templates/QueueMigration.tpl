<?php

use Marwa\Application\Migrations\AbstractMigration;
use Marwa\Application\Migrations\TableSchema;

class {{MIGRATIONNAME}} extends AbstractMigration
{


	public function up() : void
	{
		$this->table('{{MIGRATIONTABLE}}')
		->id()
		->strings('queue')
		->text('payload')
		->strings('status',10)
		->strings('priority',10)
		->text('result')
		->tinyInteger('attempts')
		->unsignedInteger('schedule_at')
		->unsignedInteger('created_at')
		->unsignedInteger('process_at')
		->create();
	}


	public function down() : void
	{
		$this->table('{{MIGRATIONTABLE}}')->drop();
	}
}
