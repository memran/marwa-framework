<?php


use Marwa\Application\Migrations\AbstractMigration;
use Marwa\Application\Migrations\TableSchema;

class {{MIGRATIONNAME}} extends AbstractMigration
{

	public function up() : void
	{
		$this->table('{{MIGRATIONTABLE}}')
		->id()
		->strings('name')
		->strings('command')
		->strings('frequency')
		->unsignedInteger('created_at')
		->create();

		$this->table('schedule_logs')
		->id()
		->strings('name')
		->text('result')
		->unsignedInteger('executed_at')
		->create();
	}

	public function down() : void
	{
		$this->table('{{MIGRATIONTABLE}}')->drop();
		$this->table('schedule_logs')->drop();
	}
}

