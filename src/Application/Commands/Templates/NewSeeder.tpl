<?php

use Marwa\Application\Migrations\AbstractSeeder;
use Marwa\Application\Facades\DB;

class {{SEEDERNAME}} extends AbstractSeeder
{
	public function run() : void
	{
			DB::table('users')->insert();
	}
}
