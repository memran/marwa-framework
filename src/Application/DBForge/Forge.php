<?php

namespace Marwa\Application\DBForge;

class Forge
{

	/**
	 * @param $tableSql
	 * @return mixed
	 */
	public static function createTable($tableSql)
	{
		return app('DB')->rawQuery($tableSql);
	}

	/**
	 * @param string $table
	 * @return int
	 */
	public static function dropIfExists(string $table)
	{
		if (static::tableExists($table)) {
			return static::dropTable($table);
		}

		return 0;

	}

	/**
	 * @param string $table
	 * @return mixed
	 */
	public static function tableExists(string $table)
	{
		$result = app('DB')->raw("SELECT * FROM information_schema.tables WHERE table_schema = '" . env('DB_NAME') . "'
    AND table_name = '$table' LIMIT 1");
		if (is_array($result)) {
			return true;
		}
		return false;
	}


	/**
	 * @param string $table
	 * @return mixed
	 */
	public static function dropTable(string $table)
	{
		return app('DB')->rawQuery("DROP TABLE {$table}");
	}


	/**
	 * @param string $table
	 * @return mixed
	 */
	public static function optimize(string $table)
	{
		return app('DB')->rawQuery("OPTIMIZE TABLE `{$table}`");
	}


	/**
	 * @param string $table
	 * @return mixed
	 */
	public static function repair(string $table)
	{

		return app('DB')->rawQuery("REPAIR TABLE `{$table}`");
	}


	/**
	 * @param string $table
	 * @return mixed
	 */
	public static function empty(string $table)
	{

		return app('DB')->rawQuery("TRUNCATE `{$table}`");
	}


	/**
	 * @param string $table
	 * @return mixed
	 */
	public static function show(string $table)
	{
		return app('DB')->rawQuery("SHOW CREATE TABLE `{$table}`");
	}

}