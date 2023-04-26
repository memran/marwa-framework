<?php

use Marwa\Application\Migrations\AbstractMigration;
use Marwa\Application\Migrations\TableSchema;

class {{MIGRATIONNAME}} extends AbstractMigration
{

	public function up() : void
	{
		$this->table('{{MIGRATIONTABLE}}')
		->id()
		->timestamps()
		->create();
	}

	public function down() : void
	{
		$this->table('{{MIGRATIONTABLE}}')->drop();
	}
}
